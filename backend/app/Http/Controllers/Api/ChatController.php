<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Display a listing of user's chats
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            $chats = Chat::forUser($user)
                ->with([
                    'users' => function ($query) use ($user) {
                        $query->where('user_id', '!=', $user->id)
                            ->select('id', 'first_name', 'last_name', 'avatar', 'last_active_at');
                    },
                    'lastMessage.user:id,first_name,last_name',
                ])
                ->active()
                ->orderBy('last_message_at', 'desc')
                ->orderBy('updated_at', 'desc')
                ->get();

            // Transform the data for frontend
            $transformedChats = $chats->map(function ($chat) use ($user) {
                return [
                    'id' => $chat->id,
                    'type' => $chat->type,
                    'name' => $chat->getDisplayName($user),
                    'avatar' => $chat->avatar_url,
                    'last_message' => $chat->lastMessage ? [
                        'content' => $chat->lastMessage->getFormattedContent(),
                        'created_at' => $chat->lastMessage->created_at,
                        'sender' => $chat->lastMessage->user->first_name,
                        'is_own' => $chat->lastMessage->user_id === $user->id,
                    ] : null,
                    'unread_count' => $chat->unread_count,
                    'participants' => $chat->users->map(function ($participant) {
                        return [
                            'id' => $participant->id,
                            'name' => $participant->first_name . ' ' . $participant->last_name,
                            'avatar' => $participant->avatar_url ?? null,
                            'is_online' => $participant->last_active_at && $participant->last_active_at->diffInMinutes(now()) < 5,
                        ];
                    }),
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'chats' => $transformedChats,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch chats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new chat (private or group)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:private,group',
                'participants' => 'required|array|min:1',
                'participants.*' => 'exists:users,id',
                'name' => 'required_if:type,group|string|max:255',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $participants = collect($request->participants)->unique();

            // For private chats, check if chat already exists
            if ($request->type === 'private') {
                if ($participants->count() !== 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Private chat must have exactly one other participant'
                    ], 422);
                }

                $otherUserId = $participants->first();
                $existingChat = Chat::where('type', 'private')
                    ->whereHas('users', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->whereHas('users', function ($query) use ($otherUserId) {
                        $query->where('user_id', $otherUserId);
                    })
                    ->first();

                if ($existingChat) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Chat already exists',
                        'data' => ['chat' => $existingChat->load('users', 'lastMessage')]
                    ]);
                }
            }

            // Create the chat
            $chat = Chat::create([
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => $user->id,
                'is_active' => true,
            ]);

            // Add participants
            $allParticipants = $participants->push($user->id)->unique();
            foreach ($allParticipants as $participantId) {
                $role = ($participantId == $user->id) ? 'owner' : 'member';
                $participant = User::find($participantId);
                $chat->addParticipant($participant, $role);
                
                // Send notification to other participants (not the creator)
                if ($participantId != $user->id) {
                    NotificationService::sendNewChatNotification($user, $participant, $chat->id);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Chat created successfully',
                'data' => [
                    'chat' => $chat->load(['users', 'lastMessage'])
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chat details and recent messages
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)
                ->with([
                    'users' => function ($query) {
                        $query->select('id', 'first_name', 'last_name', 'avatar', 'last_active_at');
                    },
                    'messages' => function ($query) {
                        $query->with('user:id,first_name,last_name,avatar')
                            ->with('replyToMessage.user:id,first_name,last_name')
                            ->orderBy('created_at', 'desc')
                            ->limit(50); // Load last 50 messages
                    }
                ])
                ->findOrFail($id);

            // Mark chat as seen
            $chat->users()->updateExistingPivot($user->id, [
                'last_seen_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'chat' => [
                        'id' => $chat->id,
                        'type' => $chat->type,
                        'name' => $chat->getDisplayName($user),
                        'description' => $chat->description,
                        'avatar' => $chat->avatar_url,
                        'participants' => $chat->users->map(function ($participant) {
                            return [
                                'id' => $participant->id,
                                'name' => $participant->first_name . ' ' . $participant->last_name,
                                'avatar' => $participant->avatar_url ?? null,
                                'is_online' => $participant->last_active_at && $participant->last_active_at->diffInMinutes(now()) < 5,
                                'role' => $participant->pivot->role,
                            ];
                        }),
                        'messages' => $chat->messages->reverse()->values(), // Reverse to show oldest first
                        'created_at' => $chat->created_at,
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chat not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update chat (rename, change description, etc.)
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($id);

            // Check if user has permission to update (owner or admin)
            $userRole = $chat->users()->where('user_id', $user->id)->first()->pivot->role;
            if (!in_array($userRole, ['owner', 'admin'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You do not have permission to update this chat'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $chat->update($request->only(['name', 'description']));

            return response()->json([
                'status' => 'success',
                'message' => 'Chat updated successfully',
                'data' => ['chat' => $chat->fresh()]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete/leave chat
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($id);

            if ($chat->type === 'private') {
                // For private chats, just mark as inactive
                $chat->update(['is_active' => false]);
            } else {
                // For group chats, remove user from participants
                $chat->removeParticipant($user);
                
                // If no participants left, mark chat as inactive
                if ($chat->users()->count() === 0) {
                    $chat->update(['is_active' => false]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Chat removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'message_id' => 'required|exists:messages,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $chat->markAsRead($user, $request->message_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Messages marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark messages as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
