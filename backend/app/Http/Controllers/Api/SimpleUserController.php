<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Friendship;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SimpleUserController extends Controller
{
    /**
     * Simple user search without complex relationships
     */
    public function search(Request $request)
    {
        Log::info('SimpleUserController@search called');
        
        try {
            $query = User::select('id', 'first_name', 'last_name', 'email', 'avatar');

            // Search by name or email
            if ($request->search) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('first_name', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $searchTerm . '%')
                      ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            $users = $query->limit(10)->get();

            // Add basic info
            $users->transform(function ($user) {
                if ($user->avatar) {
                    $user->avatar_url = asset('storage/avatars/' . $user->avatar);
                }
                $user->profile_completion = 75;
                $user->skills_count = 0;
                $user->is_friend = false;
                $user->is_blocked = false;
                $user->friend_request_sent = false;
                $user->friend_request_received = false;
                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 10,
                        'total' => $users->count(),
                    ]
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('SimpleUserController error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simple friend request with actual functionality
     */
    public function sendFriendRequest($id)
    {
        try {
            Log::info("SimpleUserController@sendFriendRequest called for user {$id}");
            
            $currentUser = Auth::user();
            $targetUser = User::find($id);

            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($currentUser->id == $targetUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot send a friend request to yourself'
                ], 400);
            }

            // Check if friendship already exists
            $existingFriendship = Friendship::where(function($query) use ($currentUser, $targetUser) {
                $query->where('user_id', $currentUser->id)
                      ->where('friend_id', $targetUser->id);
            })->orWhere(function($query) use ($currentUser, $targetUser) {
                $query->where('user_id', $targetUser->id)
                      ->where('friend_id', $currentUser->id);
            })->first();

            if ($existingFriendship) {
                if ($existingFriendship->status === 'accepted') {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are already friends with this user'
                    ], 400);
                } elseif ($existingFriendship->status === 'pending') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Friend request already sent'
                    ], 400);
                }
            }

            // Create friendship record
            Friendship::create([
                'user_id' => $currentUser->id,
                'friend_id' => $targetUser->id,
                'status' => 'pending'
            ]);

            // Send notification to target user
            NotificationService::sendFriendRequestNotification($currentUser, $targetUser);

            return response()->json([
                'success' => true,
                'message' => 'Friend request sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('sendFriendRequest error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send friend request'
            ], 500);
        }
    }

    /**
     * Simple block user with actual functionality
     */
    public function blockUser($id)
    {
        try {
            Log::info("SimpleUserController@blockUser called for user {$id}");
            
            $currentUser = Auth::user();
            $targetUser = User::find($id);

            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            if ($currentUser->id == $targetUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot block yourself'
                ], 400);
            }

            // Check if already blocked
            $existingBlock = Friendship::where('user_id', $currentUser->id)
                                     ->where('friend_id', $targetUser->id)
                                     ->where('status', 'blocked')
                                     ->first();

            if ($existingBlock) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already blocked'
                ], 400);
            }

            // Remove any existing friendship or update to blocked
            Friendship::where(function($query) use ($currentUser, $targetUser) {
                $query->where('user_id', $currentUser->id)
                      ->where('friend_id', $targetUser->id);
            })->orWhere(function($query) use ($currentUser, $targetUser) {
                $query->where('user_id', $targetUser->id)
                      ->where('friend_id', $currentUser->id);
            })->delete();

            // Create block record
            Friendship::create([
                'user_id' => $currentUser->id,
                'friend_id' => $targetUser->id,
                'status' => 'blocked'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User blocked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('blockUser error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to block user'
            ], 500);
        }
    }

    /**
     * Accept friend request
     */
    public function acceptFriendRequest($id)
    {
        try {
            Log::info("SimpleUserController@acceptFriendRequest called for user {$id}");
            
            $currentUser = Auth::user();
            $senderUser = User::find($id);

            if (!$senderUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Find the pending friend request
            $friendship = Friendship::where('user_id', $senderUser->id)
                                  ->where('friend_id', $currentUser->id)
                                  ->where('status', 'pending')
                                  ->first();

            if (!$friendship) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending friend request found'
                ], 404);
            }

            // Update status to accepted
            $friendship->update(['status' => 'accepted']);

            // Send notification to the original sender
            NotificationService::sendFriendRequestAcceptedNotification($currentUser, $senderUser);

            // Clean up friend request notifications
            NotificationService::cleanupFriendRequestNotifications($currentUser, $senderUser);

            return response()->json([
                'success' => true,
                'message' => 'Friend request accepted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('acceptFriendRequest error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept friend request'
            ], 500);
        }
    }

    /**
     * Unblock user
     */
    public function unblockUser($id)
    {
        try {
            Log::info("SimpleUserController@unblockUser called for user {$id}");
            
            $currentUser = Auth::user();
            $targetUser = User::find($id);

            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Find the block record
            $blockRecord = Friendship::where('user_id', $currentUser->id)
                                   ->where('friend_id', $targetUser->id)
                                   ->where('status', 'blocked')
                                   ->first();

            if (!$blockRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not blocked'
                ], 400);
            }

            // Remove the block record
            $blockRecord->delete();

            return response()->json([
                'success' => true,
                'message' => 'User unblocked successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('unblockUser error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock user'
            ], 500);
        }
    }
}
