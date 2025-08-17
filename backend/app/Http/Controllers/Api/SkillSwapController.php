<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkillSwap;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SkillSwapController extends Controller
{
    public function index(Request $request)
    {
        $query = SkillSwap::with(['requester', 'provider', 'requestedSkill', 'offeredSkill']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by swap type
        if ($request->has('swap_type')) {
            $query->where('swap_type', $request->swap_type);
        }

        // Filter by skill
        if ($request->has('skill_id')) {
            $query->where(function($q) use ($request) {
                $q->where('requested_skill_id', $request->skill_id)
                  ->orWhere('offered_skill_id', $request->skill_id);
            });
        }

        // Filter by format
        if ($request->has('format')) {
            $query->where('format', $request->format);
        }

        // Filter by difficulty level
        if ($request->has('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        // Only show active swaps by default
        $query->whereIn('status', ['pending', 'matched', 'in_progress']);

        $skillSwaps = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $skillSwaps
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requested_skill_id' => 'required|exists:skills,id',
            'offered_skill_id' => 'nullable|exists:skills,id',
            'swap_type' => 'required|in:skill_for_skill,free_teaching,paid_teaching',
            'price' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|integer|min:1|max:100',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'format' => 'required|in:online,offline,both',
            'preferred_times' => 'nullable|array',
            'location_preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate swap type specific requirements
        if ($request->swap_type === 'skill_for_skill' && !$request->offered_skill_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Offered skill is required for skill-for-skill swaps'
            ], 422);
        }

        if ($request->swap_type === 'paid_teaching' && !$request->price) {
            return response()->json([
                'status' => 'error',
                'message' => 'Price is required for paid teaching'
            ], 422);
        }

        $skillSwap = SkillSwap::create([
            'requester_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'requested_skill_id' => $request->requested_skill_id,
            'offered_skill_id' => $request->offered_skill_id,
            'swap_type' => $request->swap_type,
            'price' => $request->price,
            'estimated_hours' => $request->estimated_hours,
            'difficulty_level' => $request->difficulty_level,
            'format' => $request->format,
            'preferred_times' => $request->preferred_times,
            'location_preferences' => $request->location_preferences,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Skill swap request created successfully',
            'data' => ['skill_swap' => $skillSwap->load(['requester', 'requestedSkill', 'offeredSkill'])]
        ], 201);
    }

    public function show(SkillSwap $skillSwap)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'skill_swap' => $skillSwap->load([
                    'requester.profile',
                    'provider.profile',
                    'requestedSkill',
                    'offeredSkill'
                ])
            ]
        ]);
    }

    public function update(Request $request, SkillSwap $skillSwap)
    {
        // Check if user is the requester
        if ($skillSwap->requester_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Can only update if still pending
        if ($skillSwap->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only update pending skill swaps'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|nullable|numeric|min:0',
            'estimated_hours' => 'sometimes|nullable|integer|min:1|max:100',
            'format' => 'sometimes|in:online,offline,both',
            'preferred_times' => 'sometimes|nullable|array',
            'location_preferences' => 'sometimes|nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $skillSwap->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Skill swap updated successfully',
            'data' => ['skill_swap' => $skillSwap->fresh()->load(['requester', 'requestedSkill', 'offeredSkill'])]
        ]);
    }

    public function apply(Request $request, SkillSwap $skillSwap)
    {
        // Check if skill swap is still available
        if ($skillSwap->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'This skill swap is no longer available'
            ], 422);
        }

        // Check if user is not the requester
        if ($skillSwap->requester_id === $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot apply to your own skill swap'
            ], 422);
        }

        // Check if user already applied
        if ($skillSwap->provider_id === $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already applied to this skill swap'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'provider_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $skillSwap->update([
            'provider_id' => $request->user()->id,
            'provider_notes' => $request->provider_notes,
            'status' => 'matched',
            'matched_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully applied to skill swap',
            'data' => ['skill_swap' => $skillSwap->fresh()->load(['requester', 'provider', 'requestedSkill', 'offeredSkill'])]
        ]);
    }

    public function accept(Request $request, SkillSwap $skillSwap)
    {
        // Check if user is the requester
        if ($skillSwap->requester_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if skill swap is matched
        if ($skillSwap->status !== 'matched') {
            return response()->json([
                'status' => 'error',
                'message' => 'Skill swap must be matched to accept'
            ], 422);
        }

        $skillSwap->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Skill swap accepted and started',
            'data' => ['skill_swap' => $skillSwap->fresh()]
        ]);
    }

    public function complete(Request $request, SkillSwap $skillSwap)
    {
        // Check if user is either requester or provider
        if (!in_array($request->user()->id, [$skillSwap->requester_id, $skillSwap->provider_id])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if skill swap is in progress
        if ($skillSwap->status !== 'in_progress') {
            return response()->json([
                'status' => 'error',
                'message' => 'Skill swap must be in progress to complete'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $isRequester = $request->user()->id === $skillSwap->requester_id;

        if ($isRequester) {
            $skillSwap->update([
                'requester_rating' => $request->rating,
                'requester_review' => $request->review,
            ]);
        } else {
            $skillSwap->update([
                'provider_rating' => $request->rating,
                'provider_review' => $request->review,
            ]);
        }

        // If both parties have rated, mark as completed
        if ($skillSwap->requester_rating && $skillSwap->provider_rating) {
            $skillSwap->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Award XP to both users
            $this->awardXP($skillSwap);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Skill swap reviewed successfully',
            'data' => ['skill_swap' => $skillSwap->fresh()]
        ]);
    }

    public function cancel(Request $request, SkillSwap $skillSwap)
    {
        // Check if user is either requester or provider
        if (!in_array($request->user()->id, [$skillSwap->requester_id, $skillSwap->provider_id])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Can only cancel if not completed
        if ($skillSwap->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel completed skill swap'
            ], 422);
        }

        $skillSwap->update(['status' => 'cancelled']);

        return response()->json([
            'status' => 'success',
            'message' => 'Skill swap cancelled successfully'
        ]);
    }

    public function getUserSkillSwaps(Request $request)
    {
        $user = $request->user();
        $type = $request->get('type', 'all'); // 'requested', 'provided', 'all'

        $query = SkillSwap::with(['requester', 'provider', 'requestedSkill', 'offeredSkill']);

        if ($type === 'requested') {
            $query->where('requester_id', $user->id);
        } elseif ($type === 'provided') {
            $query->where('provider_id', $user->id);
        } else {
            $query->where(function($q) use ($user) {
                $q->where('requester_id', $user->id)
                  ->orWhere('provider_id', $user->id);
            });
        }

        $skillSwaps = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 20));

        return response()->json([
            'status' => 'success',
            'data' => $skillSwaps
        ]);
    }

    public function getRecommendations(Request $request)
    {
        $user = $request->user();
        
        // Get user's wanted skills
        $wantedSkills = $user->skills()
                            ->where('type', 'wanted')
                            ->pluck('skill_id')
                            ->toArray();

        if (empty($wantedSkills)) {
            return response()->json([
                'status' => 'success',
                'data' => ['skill_swaps' => []]
            ]);
        }

        // Find skill swaps that offer skills the user wants
        $recommendations = SkillSwap::with(['requester', 'requestedSkill', 'offeredSkill'])
                                   ->where('status', 'pending')
                                   ->where('requester_id', '!=', $user->id)
                                   ->whereIn('requested_skill_id', $wantedSkills)
                                   ->orderBy('created_at', 'desc')
                                   ->limit(10)
                                   ->get();

        return response()->json([
            'status' => 'success',
            'data' => ['skill_swaps' => $recommendations]
        ]);
    }

    private function awardXP(SkillSwap $skillSwap)
    {
        // Award XP to both users for completing a skill swap
        $xpAmount = 50; // Base XP for completing a skill swap

        // Award to requester
        $skillSwap->requester->increment('xp', $xpAmount);
        
        // Award to provider
        if ($skillSwap->provider) {
            $skillSwap->provider->increment('xp', $xpAmount);
        }

        // Check for level ups
        $this->checkLevelUp($skillSwap->requester);
        if ($skillSwap->provider) {
            $this->checkLevelUp($skillSwap->provider);
        }
    }

    private function checkLevelUp(User $user)
    {
        $currentLevel = $user->level;
        $newLevel = floor($user->xp / 100) + 1; // Level up every 100 XP

        if ($newLevel > $currentLevel) {
            $user->update(['level' => $newLevel]);
            
            // Award level up bonus XP
            $user->increment('xp', 25);
        }
    }
}
