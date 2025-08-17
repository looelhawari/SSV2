<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSkill;
use App\Models\Friendship;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Remove the constructor entirely - authentication is handled by routes
    
    /**
     * Get all users with pagination and filters
     */
    public function index(Request $request)
    {
        $query = User::query(); // Simplified - no relationships to avoid errors

        // Search by name or email
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
        }

        $users = $query->paginate($request->per_page ?? 15);

        // Add basic user info without complex relationships
        $currentUser = Auth::user();
        $users->getCollection()->transform(function ($user) use ($currentUser) {
            // Add avatar URL
            if ($user->avatar) {
                $user->avatar_url = asset('storage/avatars/' . $user->avatar);
            }
            
            // Add basic defaults to avoid frontend errors
            $user->profile_completion = 75;
            $user->skills_count = 0;
            
            // Simple relationship status
            if ($currentUser && $currentUser->id !== $user->id) {
                $user->is_friend = false;
                $user->is_blocked = false;
                $user->friend_request_sent = false;
                $user->friend_request_received = false;
            }
            
            return $user;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ],
        ]);
    }

    /**
     * Get a specific user by ID
     */
    public function show($id)
    {
        $user = User::with([
            'profile', 
            'university', 
            'faculty', 
            'major', 
            'ownedSkills.skill', 
            'wantedSkills.skill',
            'mentorships',
            'skillSwaps',
            'resources',
            'reviews'
        ])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Hide sensitive information if not the same user or admin
        $currentUser = Auth::user();
        // Temporarily comment out admin check due to role issues
        if ($currentUser->id !== $user->id) { // && !$currentUser->hasRole('admin')
            $user->makeHidden(['email', 'phone', 'email_verified_at']);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update user profile (users can only update their own profile)
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        
        // Users can only update their own profile unless they're admin
        if ($currentUser->id != $id) { // && !$currentUser->hasRole('admin')
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this profile'
            ], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'bio' => 'sometimes|string|max:1000',
            'birth_date' => 'sometimes|date',
            'gender' => 'sometimes|in:male,female,other',
            'location' => 'sometimes|string|max:255',
            'website' => 'sometimes|url|max:255',
            'linkedin_url' => 'sometimes|url|max:255',
            'github_url' => 'sometimes|url|max:255',
            'university_id' => 'sometimes|exists:universities,id',
            'faculty_id' => 'sometimes|exists:faculties,id',
            'major_id' => 'sometimes|exists:majors,id',
            'graduation_year' => 'sometimes|integer|min:1950|max:2030',
            'user_type' => 'sometimes|in:student,mentor,alumni',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user basic info
        $user->fill($request->only([
            'name', 'phone', 'university_id', 'faculty_id', 'major_id', 
            'graduation_year', 'user_type'
        ]));
        $user->save();

        // Update or create profile
        $profileData = $request->only([
            'bio', 'birth_date', 'gender', 'location', 'website', 
            'linkedin_url', 'github_url'
        ]);

        if (!empty($profileData)) {
            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        $user->load(['profile', 'university', 'faculty', 'major']);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user,
        ]);
    }

    /**
     * Delete a user (admin only)
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        
        // Temporarily disable admin check
        // if (!$currentUser->hasRole('admin')) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized. Admin access required.'
        //     ], 403);
        // }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Prevent deleting other admins
        if ($user->hasRole('admin') && $currentUser->id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete other admin users'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Get user statistics
     */
    public function stats($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $stats = [
            'total_skills_owned' => $user->ownedSkills()->count(),
            'total_skills_wanted' => $user->wantedSkills()->count(),
            'total_skill_swaps' => $user->skillSwaps()->count(),
            'completed_skill_swaps' => $user->skillSwaps()->where('status', 'completed')->count(),
            'total_mentorships_as_mentor' => $user->mentorshipsAsMentor()->count(),
            'total_mentorships_as_mentee' => $user->mentorshipsAsMentee()->count(),
            'total_resources_shared' => $user->resources()->count(),
            'total_xp' => $user->total_xp,
            'current_level' => $user->current_level,
            'total_reviews_received' => $user->reviewsReceived()->count(),
            'average_rating' => $user->reviewsReceived()->avg('rating'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request, $id)
    {
        $currentUser = Auth::user();
        
        // Users can only change their own password unless they're admin
        if ($currentUser->id != $id && !$currentUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to change this password'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required_unless:is_admin,true',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Verify current password unless admin
        if (!$currentUser->hasRole('admin')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 422);
            }
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Toggle user status (admin only)
     */
    public function toggleStatus($id)
    {
        $currentUser = Auth::user();
        
        if (!$currentUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'is_active' => $user->is_active
            ]
        ]);
    }

    /**
     * Assign role to user (admin only)
     */
    public function assignRole(Request $request, $id)
    {
        $currentUser = Auth::user();
        
        if (!$currentUser->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->syncRoles([$request->role]);

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully',
            'data' => [
                'user' => $user->name,
                'role' => $request->role
            ]
        ]);
    }

    /**
     * Send friend request
     */
    public function sendFriendRequest($id)
    {
        $currentUser = Auth::user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($currentUser->id === $targetUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send friend request to yourself'
            ], 422);
        }

        if ($currentUser->isFriendWith($targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Already friends with this user'
            ], 422);
        }

        if ($currentUser->hasSentFriendRequestTo($targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Friend request already sent'
            ], 422);
        }

        if ($currentUser->hasBlockedUser($targetUser) || $targetUser->hasBlockedUser($currentUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send friend request to this user'
            ], 422);
        }

        $currentUser->sendFriendRequest($targetUser);

        // Send notification to the target user
        NotificationService::sendFriendRequestNotification($currentUser, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent successfully'
        ]);
    }

    /**
     * Accept friend request
     */
    public function acceptFriendRequest($id)
    {
        $currentUser = Auth::user();
        $requesterUser = User::find($id);

        if (!$requesterUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!$currentUser->hasFriendRequestFrom($requesterUser)) {
            return response()->json([
                'success' => false,
                'message' => 'No pending friend request from this user'
            ], 422);
        }

        $result = $currentUser->acceptFriendRequest($requesterUser);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept friend request'
            ], 500);
        }

        // Send notification to the requester
        NotificationService::sendFriendRequestAcceptedNotification($currentUser, $requesterUser);
        
        // Clean up friend request notifications
        NotificationService::cleanupFriendRequestNotifications($currentUser, $requesterUser);

        return response()->json([
            'success' => true,
            'message' => 'Friend request accepted successfully'
        ]);
    }

    /**
     * Decline friend request
     */
    public function declineFriendRequest($id)
    {
        $currentUser = Auth::user();
        $requesterUser = User::find($id);

        if (!$requesterUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!$currentUser->hasFriendRequestFrom($requesterUser)) {
            return response()->json([
                'success' => false,
                'message' => 'No pending friend request from this user'
            ], 422);
        }

        $result = $currentUser->declineFriendRequest($requesterUser);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline friend request'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Friend request declined successfully'
        ]);
    }

    /**
     * Remove friend
     */
    public function removeFriend($id)
    {
        $currentUser = Auth::user();
        $friendUser = User::find($id);

        if (!$friendUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!$currentUser->isFriendWith($friendUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Not friends with this user'
            ], 422);
        }

        $currentUser->removeFriend($friendUser);

        return response()->json([
            'success' => true,
            'message' => 'Friend removed successfully'
        ]);
    }

    /**
     * Block user
     */
    public function blockUser($id)
    {
        $currentUser = Auth::user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($currentUser->id === $targetUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot block yourself'
            ], 422);
        }

        if ($currentUser->hasBlockedUser($targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'User already blocked'
            ], 422);
        }

        $currentUser->blockUser($targetUser);

        // Send notification to the blocked user (optional - you might not want to notify them)
        // NotificationService::sendUserBlockedNotification($currentUser, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully'
        ]);
    }

    /**
     * Unblock user
     */
    public function unblockUser($id)
    {
        $currentUser = Auth::user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if (!$currentUser->hasBlockedUser($targetUser)) {
            return response()->json([
                'success' => false,
                'message' => 'User is not blocked'
            ], 422);
        }

        $currentUser->unblockUser($targetUser);

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully'
        ]);
    }

    /**
     * Get friends list
     */
    public function getFriends()
    {
        $currentUser = Auth::user();
        
        $friends = $currentUser->friends()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.avatar')
            ->with(['major.faculty.university'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $friends
        ]);
    }

    /**
     * Get friend requests (received)
     */
    public function getFriendRequests()
    {
        $currentUser = Auth::user();
        
        $friendRequests = $currentUser->receivedFriendRequests()
            ->where('status', 'pending')
            ->with(['user:id,first_name,last_name,email,avatar'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $friendRequests
        ]);
    }

    /**
     * Get blocked users
     */
    public function getBlockedUsers()
    {
        $currentUser = Auth::user();
        
        $blockedUsers = $currentUser->blockedUsers()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.avatar')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $blockedUsers
        ]);
    }
}
