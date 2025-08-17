<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Events\UserJoinedChat;
use App\Events\UserLeftChat;
use App\Events\UserOnlineStatus;
use App\Events\MessageReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class EnhancedChatController extends Controller
{
    /**
     * Get all chats for the authenticated user with enhanced details
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('📋 Fetching enhanced chats for user', ['user_id' => $user->id]);

            // Get chats with participants, last message, and unread counts
            $chats = Chat::with([
                'participants:id,first_name,last_name,avatar,email',
                'messages' => function($query) {
                    $query->latest()->limit(1)->with('user:id,first_name,last_name,avatar');
                }
            ])
            ->whereHas('participants', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest('updated_at')
            ->get()
            ->map(function($chat) use ($user) {
                // Calculate unread count
                $unreadCount = Message::where('chat_id', $chat->id)
                    ->where('user_id', '!=', $user->id)
                    ->whereDoesntHave('readers', function($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->count();

                // Get last message preview
                $lastMessage = $chat->messages->first();
                $lastMessagePreview = null;
                if ($lastMessage) {
                    $lastMessagePreview = [
                        'content' => $lastMessage->content,
                        'sender_name' => $lastMessage->user->first_name,
                        'created_at' => $lastMessage->created_at->toISOString(),
                        'is_own' => $lastMessage->user_id === $user->id
                    ];
                }

                // Determine display name and avatar for the chat
                $displayName = $chat->name;
                $displayAvatar = $chat->avatar;

                if ($chat->type === 'private') {
                    // For private chats, use the other participant's details
                    $otherParticipant = $chat->participants->where('id', '!=', $user->id)->first();
                    if ($otherParticipant) {
                        $displayName = $otherParticipant->first_name . ' ' . $otherParticipant->last_name;
                        $displayAvatar = $otherParticipant->avatar;
                    }
                }

                return [
                    'id' => $chat->id,
                    'type' => $chat->type,
                    'name' => $chat->name,
                    'display_name' => $displayName,
                    'avatar' => $chat->avatar,
                    'display_avatar' => $displayAvatar,
                    'description' => $chat->description,
                    'last_message_preview' => $lastMessagePreview,
                    'last_message_at' => $lastMessage?->created_at?->toISOString(),
                    'unread_count' => $unreadCount,
                    'participants' => $chat->participants->map(function($participant) {
                        return [
                            'id' => $participant->id,
                            'first_name' => $participant->first_name,
                            'last_name' => $participant->last_name,
                            'avatar' => $participant->avatar,
                            'email' => $participant->email
                        ];
                    }),
                    'created_at' => $chat->created_at->toISOString(),
                    'updated_at' => $chat->updated_at->toISOString()
                ];
            });

            Log::info('✅ Enhanced chats fetched successfully', ['count' => $chats->count()]);

            return response()->json([
                'success' => true,
                'message' => 'Enhanced chats retrieved successfully',
                'data' => [
                    'chats' => $chats
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching enhanced chats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enhanced chats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new enhanced chat
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('🆕 Creating enhanced chat', ['user_id' => $user->id, 'request' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:private,group',
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'participants' => 'required|array|min:1',
                'participants.*' => 'exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $participantIds = $request->participants;
            
            // Add current user to participants if not already included
            if (!in_array($user->id, $participantIds)) {
                $participantIds[] = $user->id;
            }

            // For private chats, check if chat already exists
            if ($request->type === 'private' && count($participantIds) === 2) {
                $existingChat = Chat::where('type', 'private')
                    ->whereHas('participants', function($query) use ($participantIds) {
                        $query->whereIn('user_id', $participantIds);
                    }, '=', 2)
                    ->with(['participants:id,first_name,last_name,avatar,email'])
                    ->first();

                if ($existingChat) {
                    Log::info('📱 Returning existing private chat', ['chat_id' => $existingChat->id]);
                    
                    // Broadcast user joined event
                    broadcast(new UserJoinedChat($existingChat, $user))->toOthers();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Chat already exists',
                        'data' => [
                            'chat' => $this->formatChatResponse($existingChat, $user)
                        ]
                    ]);
                }
            }

            DB::beginTransaction();

            // Create the chat
            $chat = Chat::create([
                'type' => $request->type,
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => $user->id
            ]);

            // Attach participants
            $chat->participants()->attach($participantIds);

            // Load relationships
            $chat->load(['participants:id,first_name,last_name,avatar,email']);

            DB::commit();

            Log::info('✅ Enhanced chat created successfully', ['chat_id' => $chat->id]);

            // Broadcast to all participants that they joined the chat
            foreach ($chat->participants as $participant) {
                if ($participant->id !== $user->id) {
                    broadcast(new UserJoinedChat($chat, $participant));
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Enhanced chat created successfully',
                'data' => [
                    'chat' => $this->formatChatResponse($chat, $user)
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error creating enhanced chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create enhanced chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific enhanced chat with details
     */
    public function show(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('👁️ Fetching enhanced chat details', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $chat->load(['participants:id,first_name,last_name,avatar,email']);

            // Mark user as online in this chat
            $this->markUserOnline($user, $chat);

            // Broadcast user joined if not already in chat
            broadcast(new UserJoinedChat($chat, $user))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Enhanced chat details retrieved successfully',
                'data' => [
                    'chat' => $this->formatChatResponse($chat, $user)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching enhanced chat details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enhanced chat details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update enhanced chat settings
     */
    public function update(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('🔧 Updating enhanced chat', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'avatar' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $chat->update($request->only(['name', 'description', 'avatar']));
            $chat->load(['participants:id,first_name,last_name,avatar,email']);

            Log::info('✅ Enhanced chat updated successfully', ['chat_id' => $chat->id]);

            return response()->json([
                'success' => true,
                'message' => 'Enhanced chat updated successfully',
                'data' => [
                    'chat' => $this->formatChatResponse($chat, $user)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error updating enhanced chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update enhanced chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Leave a chat
     */
    public function leave(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('🚪 User leaving enhanced chat', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a participant of this chat'
                ], 403);
            }

            DB::beginTransaction();

            // Remove user from chat
            $chat->participants()->detach($user->id);

            // Mark user as offline
            $this->markUserOffline($user, $chat);

            // Broadcast user left event
            broadcast(new UserLeftChat($chat, $user))->toOthers();

            // If no participants left, delete the chat
            if ($chat->participants()->count() === 0) {
                $chat->delete();
                Log::info('🗑️ Chat deleted as no participants remain', ['chat_id' => $chat->id]);
            }

            DB::commit();

            Log::info('✅ User left enhanced chat successfully', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Left chat successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error leaving enhanced chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to leave chat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add participants to a group chat
     */
    public function addParticipants(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('👥 Adding participants to enhanced chat', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            // Check if user is participant and chat is group
            if (!$chat->participants()->where('user_id', $user->id)->exists() || $chat->type !== 'group') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or invalid chat type'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'participants' => 'required|array|min:1',
                'participants.*' => 'exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newParticipantIds = $request->participants;
            
            // Filter out existing participants
            $existingParticipantIds = $chat->participants()->pluck('user_id')->toArray();
            $participantsToAdd = array_diff($newParticipantIds, $existingParticipantIds);

            if (empty($participantsToAdd)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All specified users are already participants'
                ], 400);
            }

            DB::beginTransaction();

            // Add new participants
            $chat->participants()->attach($participantsToAdd);

            // Load new participants
            $newParticipants = User::whereIn('id', $participantsToAdd)->get();

            // Broadcast user joined events
            foreach ($newParticipants as $participant) {
                broadcast(new UserJoinedChat($chat, $participant));
            }

            DB::commit();

            $chat->load(['participants:id,first_name,last_name,avatar,email']);

            Log::info('✅ Participants added to enhanced chat successfully', [
                'chat_id' => $chat->id, 
                'added_count' => count($participantsToAdd)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participants added successfully',
                'data' => [
                    'chat' => $this->formatChatResponse($chat, $user),
                    'added_participants' => $newParticipants->map(function($participant) {
                        return [
                            'id' => $participant->id,
                            'first_name' => $participant->first_name,
                            'last_name' => $participant->last_name,
                            'avatar' => $participant->avatar,
                            'email' => $participant->email
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error adding participants to enhanced chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add participants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get online users in a chat
     */
    public function getOnlineUsers(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('🟢 Fetching online users for enhanced chat', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $onlineUsers = $chat->participants()
                ->where('users.id', '!=', $user->id)
                ->get()
                ->filter(function($participant) use ($chat) {
                    return Cache::has("chat_online_{$chat->id}_{$participant->id}");
                })
                ->map(function($participant) use ($chat) {
                    return [
                        'id' => $participant->id,
                        'first_name' => $participant->first_name,
                        'last_name' => $participant->last_name,
                        'avatar' => $participant->avatar,
                        'last_seen' => Cache::get("chat_online_{$chat->id}_{$participant->id}")
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Online users retrieved successfully',
                'data' => [
                    'online_users' => $onlineUsers->values()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching online users for enhanced chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch online users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('👁️ Marking messages as read in enhanced chat', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'message_id' => 'nullable|exists:messages,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            if ($request->has('message_id')) {
                // Mark specific message as read
                $message = Message::where('id', $request->message_id)
                    ->where('chat_id', $chat->id)
                    ->first();

                if ($message) {
                    $message->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);
                }
            } else {
                // Mark all messages in chat as read
                $messages = Message::where('chat_id', $chat->id)
                    ->where('user_id', '!=', $user->id)
                    ->get();

                foreach ($messages as $message) {
                    $message->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);
                }
            }

            DB::commit();

            Log::info('✅ Messages marked as read successfully', ['chat_id' => $chat->id, 'user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Messages marked as read successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error marking messages as read', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format chat response with enhanced details
     */
    private function formatChatResponse(Chat $chat, User $user): array
    {
        // Determine display name and avatar for the chat
        $displayName = $chat->name;
        $displayAvatar = $chat->avatar;

        if ($chat->type === 'private') {
            // For private chats, use the other participant's details
            $otherParticipant = $chat->participants->where('id', '!=', $user->id)->first();
            if ($otherParticipant) {
                $displayName = $otherParticipant->first_name . ' ' . $otherParticipant->last_name;
                $displayAvatar = $otherParticipant->avatar;
            }
        }

        return [
            'id' => $chat->id,
            'type' => $chat->type,
            'name' => $chat->name,
            'display_name' => $displayName,
            'avatar' => $chat->avatar,
            'display_avatar' => $displayAvatar,
            'description' => $chat->description,
            'participants' => $chat->participants->map(function($participant) {
                return [
                    'id' => $participant->id,
                    'first_name' => $participant->first_name,
                    'last_name' => $participant->last_name,
                    'avatar' => $participant->avatar,
                    'email' => $participant->email
                ];
            }),
            'created_at' => $chat->created_at->toISOString(),
            'updated_at' => $chat->updated_at->toISOString()
        ];
    }

    /**
     * Mark user as online in chat
     */
    private function markUserOnline(User $user, Chat $chat): void
    {
        try {
            $cacheKey = "chat_online_{$chat->id}_{$user->id}";
            Cache::put($cacheKey, now()->toISOString(), 300); // 5 minutes

            // Broadcast online status
            broadcast(new UserOnlineStatus($user, $chat->id, true));

            Log::info('🟢 User marked as online', ['chat_id' => $chat->id, 'user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('❌ Error marking user online', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Mark user as offline in chat
     */
    private function markUserOffline(User $user, Chat $chat): void
    {
        try {
            $cacheKey = "chat_online_{$chat->id}_{$user->id}";
            Cache::forget($cacheKey);

            // Broadcast offline status
            broadcast(new UserOnlineStatus($user, $chat->id, false));

            Log::info('🔴 User marked as offline', ['chat_id' => $chat->id, 'user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('❌ Error marking user offline', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
                'user_id' => $user->id
            ]);
        }
    }
}
