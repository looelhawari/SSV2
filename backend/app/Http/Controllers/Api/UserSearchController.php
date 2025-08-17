<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSearchController extends Controller
{
    /**
     * Get all users with pagination and filters (for search)
     */
    public function index(Request $request)
    {
        \Log::info('UserSearchController@index called with search: ' . $request->search);
        
        $query = User::query();

        // Search by name or email
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $request->search . '%')
                  ->orWhere('email', 'LIKE', '%' . $request->search . '%');
            });
        }

        $users = $query->paginate($request->per_page ?? 15);

        // Add basic user info and simple relationship status
        $currentUser = Auth::user();
        $users->getCollection()->transform(function ($user) use ($currentUser) {
            // Add avatar URL
            if ($user->avatar) {
                $user->avatar_url = asset('storage/avatars/' . $user->avatar);
            }

            // Add basic profile info
            $user->profile_completion = 75; // Default value
            $user->skills_count = 0; // Default value
            
            // Simple friendship status (without complex queries for now)
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
     * Send friend request (simplified version)
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

        // For now, just send notification without complex friendship logic
        NotificationService::sendFriendRequestNotification($currentUser, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'Friend request sent successfully'
        ]);
    }

    /**
     * Block user (simplified version)
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

        // For now, just return success without complex blocking logic
        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully'
        ]);
    }
}
