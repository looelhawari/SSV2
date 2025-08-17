<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mentorship;
use App\Models\User;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MentorshipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all mentorships with filters
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Mentorship::with(['mentor', 'mentee', 'skill']);

        // Filter by user involvement
        if ($request->my_mentorships) {
            $query->where(function($q) use ($user) {
                $q->where('mentor_id', $user->id)
                  ->orWhere('mentee_id', $user->id);
            });
        }

        // Filter by role
        if ($request->as_mentor) {
            $query->where('mentor_id', $user->id);
        }
        if ($request->as_mentee) {
            $query->where('mentee_id', $user->id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by skill
        if ($request->skill_id) {
            $query->where('skill_id', $request->skill_id);
        }

        // Filter by mentorship type
        if ($request->mentorship_type) {
            $query->where('mentorship_type', $request->mentorship_type);
        }

        $mentorships = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $mentorships,
        ]);
    }

    /**
     * Create a new mentorship request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mentor_id' => 'required|exists:users,id',
            'skill_id' => 'required|exists:skills,id',
            'mentorship_type' => 'required|in:free,paid,skill_swap',
            'session_type' => 'required|in:one_time,ongoing',
            'duration_weeks' => 'nullable|integer|min:1|max:52',
            'sessions_per_week' => 'nullable|integer|min:1|max:7',
            'price_per_session' => 'nullable|numeric|min:0',
            'preferred_schedule' => 'nullable|string',
            'learning_goals' => 'required|string|max:1000',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Check if user is trying to mentor themselves
        if ($user->id == $request->mentor_id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot request mentorship from yourself'
            ], 422);
        }

        // Check if mentor exists and has the skill
        $mentor = User::find($request->mentor_id);
        if (!$mentor->ownedSkills()->where('skill_id', $request->skill_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Mentor does not have the requested skill'
            ], 422);
        }

        // Check for existing active mentorship
        $existingMentorship = Mentorship::where('mentor_id', $request->mentor_id)
            ->where('mentee_id', $user->id)
            ->where('skill_id', $request->skill_id)
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if ($existingMentorship) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active or pending mentorship request with this mentor for this skill'
            ], 422);
        }

        $mentorship = Mentorship::create([
            'mentor_id' => $request->mentor_id,
            'mentee_id' => $user->id,
            'skill_id' => $request->skill_id,
            'mentorship_type' => $request->mentorship_type,
            'session_type' => $request->session_type,
            'duration_weeks' => $request->duration_weeks,
            'sessions_per_week' => $request->sessions_per_week,
            'price_per_session' => $request->price_per_session,
            'preferred_schedule' => $request->preferred_schedule,
            'learning_goals' => $request->learning_goals,
            'message' => $request->message,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $mentorship->load(['mentor', 'mentee', 'skill']);

        // Award XP for requesting mentorship
        $user->addXP(5, 'Requested mentorship');

        return response()->json([
            'success' => true,
            'message' => 'Mentorship request created successfully',
            'data' => $mentorship,
        ], 201);
    }

    /**
     * Get a specific mentorship
     */
    public function show($id)
    {
        $user = Auth::user();
        $mentorship = Mentorship::with(['mentor', 'mentee', 'skill', 'sessions', 'reviews'])->find($id);

        if (!$mentorship) {
            return response()->json([
                'success' => false,
                'message' => 'Mentorship not found'
            ], 404);
        }

        // Check if user is involved in this mentorship
        if ($mentorship->mentor_id !== $user->id && $mentorship->mentee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this mentorship'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $mentorship,
        ]);
    }

    /**
     * Update mentorship (for status changes, scheduling, etc.)
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $mentorship = Mentorship::find($id);

        if (!$mentorship) {
            return response()->json([
                'success' => false,
                'message' => 'Mentorship not found'
            ], 404);
        }

        // Check if user is involved in this mentorship
        if ($mentorship->mentor_id !== $user->id && $mentorship->mentee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this mentorship'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:pending,active,completed,cancelled',
            'actual_schedule' => 'sometimes|string',
            'notes' => 'sometimes|string|max:1000',
            'completion_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle status changes
        if ($request->has('status')) {
            $newStatus = $request->status;
            
            // Only mentor can accept/reject pending requests
            if ($mentorship->status === 'pending' && in_array($newStatus, ['active', 'cancelled']) && $user->id !== $mentorship->mentor_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the mentor can accept or reject mentorship requests'
                ], 403);
            }

            // Handle status-specific updates
            if ($newStatus === 'active' && $mentorship->status === 'pending') {
                $mentorship->accepted_at = now();
                $mentorship->started_at = now();
                
                // Award XP for accepting mentorship
                $mentorship->mentor->addXP(10, 'Accepted mentorship request');
                $mentorship->mentee->addXP(5, 'Mentorship request accepted');
            }

            if ($newStatus === 'completed') {
                $mentorship->completed_at = $request->completion_date ?? now();
                
                // Award XP for completing mentorship
                $mentorship->mentor->addXP(50, 'Completed mentorship');
                $mentorship->mentee->addXP(30, 'Completed mentorship as mentee');
            }

            $mentorship->status = $newStatus;
        }

        // Update other fields
        $mentorship->fill($request->only(['actual_schedule', 'notes']));
        $mentorship->save();

        $mentorship->load(['mentor', 'mentee', 'skill']);

        return response()->json([
            'success' => true,
            'message' => 'Mentorship updated successfully',
            'data' => $mentorship,
        ]);
    }

    /**
     * Delete/Cancel a mentorship
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $mentorship = Mentorship::find($id);

        if (!$mentorship) {
            return response()->json([
                'success' => false,
                'message' => 'Mentorship not found'
            ], 404);
        }

        // Check if user is involved in this mentorship
        if ($mentorship->mentor_id !== $user->id && $mentorship->mentee_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this mentorship'
            ], 403);
        }

        // Only allow deletion if status is pending or active
        if (!in_array($mentorship->status, ['pending', 'active'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed or cancelled mentorships'
            ], 422);
        }

        $mentorship->status = 'cancelled';
        $mentorship->cancelled_at = now();
        $mentorship->save();

        return response()->json([
            'success' => true,
            'message' => 'Mentorship cancelled successfully',
        ]);
    }

    /**
     * Find available mentors for a skill
     */
    public function findMentors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skill_id' => 'required|exists:skills,id',
            'mentorship_type' => 'sometimes|in:free,paid,skill_swap',
            'min_rating' => 'sometimes|numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $skillId = $request->skill_id;

        // Find users who own this skill and can mentor
        $query = User::whereHas('ownedSkills', function($q) use ($skillId) {
            $q->where('skill_id', $skillId)
              ->where('can_mentor', true);
        })->where('id', '!=', $user->id)
        ->with(['profile', 'ownedSkills' => function($q) use ($skillId) {
            $q->where('skill_id', $skillId);
        }]);

        // Filter by mentorship type preference
        if ($request->mentorship_type) {
            $query->whereHas('profile', function($q) use ($request) {
                $q->where('mentorship_preferences', 'LIKE', '%' . $request->mentorship_type . '%');
            });
        }

        // Filter by minimum rating
        if ($request->min_rating) {
            $query->whereHas('reviewsReceived', function($q) use ($request) {
                $q->havingRaw('AVG(rating) >= ?', [$request->min_rating]);
            });
        }

        $mentors = $query->get()->map(function($mentor) {
            $mentor->average_rating = $mentor->reviewsReceived()->avg('rating') ?? 0;
            $mentor->total_mentorships = $mentor->mentorshipsAsMentor()->where('status', 'completed')->count();
            return $mentor;
        });

        return response()->json([
            'success' => true,
            'data' => $mentors,
        ]);
    }

    /**
     * Get mentorship statistics
     */
    public function stats()
    {
        $user = Auth::user();

        $stats = [
            'as_mentor' => [
                'total_requests' => $user->mentorshipsAsMentor()->count(),
                'pending_requests' => $user->mentorshipsAsMentor()->where('status', 'pending')->count(),
                'active_mentorships' => $user->mentorshipsAsMentor()->where('status', 'active')->count(),
                'completed_mentorships' => $user->mentorshipsAsMentor()->where('status', 'completed')->count(),
                'average_rating' => $user->reviewsReceived()->avg('rating') ?? 0,
            ],
            'as_mentee' => [
                'total_requests' => $user->mentorshipsAsMentee()->count(),
                'pending_requests' => $user->mentorshipsAsMentee()->where('status', 'pending')->count(),
                'active_mentorships' => $user->mentorshipsAsMentee()->where('status', 'active')->count(),
                'completed_mentorships' => $user->mentorshipsAsMentee()->where('status', 'completed')->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
