<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Events\MessageReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class EnhancedMessageController extends Controller
{
    /**
     * Get messages for a specific chat with enhanced features
     */
    public function index(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('📝 Fetching enhanced messages', [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'request' => $request->only(['page', 'limit', 'search'])
            ]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 50), 100); // Max 100 messages per request
            $search = $request->get('search');

            $query = Message::with([
                'user:id,first_name,last_name,avatar',
                'replyToMessage:id,content,user_id',
                'replyToMessage.user:id,first_name,last_name',
                'reactions'
            ])
            ->where('chat_id', $chat->id);

            // Add search functionality
            if ($search) {
                $query->where('content', 'LIKE', "%{$search}%");
            }

            $messages = $query->latest()
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get()
                ->reverse()
                ->values()
                ->map(function($message) {
                    return $this->formatMessageResponse($message);
                });

            // Get total count for pagination
            $totalCount = Message::where('chat_id', $chat->id)->count();

            Log::info('✅ Enhanced messages fetched successfully', [
                'chat_id' => $chat->id,
                'count' => $messages->count(),
                'page' => $page,
                'total' => $totalCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enhanced messages retrieved successfully',
                'data' => [
                    'messages' => $messages,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $totalCount,
                        'last_page' => ceil($totalCount / $limit),
                        'has_more' => ($page * $limit) < $totalCount
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching enhanced messages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enhanced messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a new enhanced message
     */
    public function store(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('📨 Sending enhanced message', [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'request' => $request->except(['file'])
            ]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required_without:file|string|max:5000',
                'type' => 'required|in:text,image,file,audio,video,voice',
                'reply_to_message_id' => 'nullable|exists:messages,id',
                'file' => 'nullable|file|max:50000', // 50MB max
                'temp_id' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate reply message belongs to same chat
            if ($request->reply_to_message_id) {
                $replyMessage = Message::where('id', $request->reply_to_message_id)
                    ->where('chat_id', $chat->id)
                    ->first();

                if (!$replyMessage) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reply message not found in this chat'
                    ], 404);
                }
            }

            DB::beginTransaction();

            $messageData = [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'content' => $request->content ?? '',
                'type' => $request->type,
                'reply_to_message_id' => $request->reply_to_message_id,
                'metadata' => []
            ];

            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileInfo = $this->handleFileUpload($file, $request->type);
                
                $messageData['file_name'] = $fileInfo['name'];
                $messageData['file_size'] = $fileInfo['size'];
                $messageData['file_url'] = $fileInfo['url'];
                $messageData['metadata'] = $fileInfo['metadata'];
                
                // Set content to file name if no content provided
                if (empty($messageData['content'])) {
                    $messageData['content'] = $fileInfo['name'];
                }
            }

            $message = Message::create($messageData);

            // Load relationships
            $message->load([
                'user:id,first_name,last_name,avatar',
                'replyToMessage:id,content,user_id',
                'replyToMessage.user:id,first_name,last_name'
            ]);

            // Update chat's last activity
            $chat->touch();

            DB::commit();

            Log::info('📤 Broadcasting enhanced message', ['message_id' => $message->id, 'chat_id' => $chat->id]);

            // Broadcast the message
            broadcast(new MessageSent($message))->toOthers();

            Log::info('✅ Enhanced message sent successfully', ['message_id' => $message->id]);

            return response()->json([
                'success' => true,
                'message' => 'Enhanced message sent successfully',
                'data' => [
                    'message' => $this->formatMessageResponse($message),
                    'temp_id' => $request->temp_id
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error sending enhanced message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send enhanced message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update/edit a message
     */
    public function update(Request $request, Chat $chat, Message $message): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('✏️ Editing enhanced message', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
                'user_id' => $user->id
            ]);

            // Check if user is the message sender
            if ($message->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to edit this message'
                ], 403);
            }

            // Check if message belongs to chat
            if ($message->chat_id !== $chat->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message does not belong to this chat'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:5000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if message is within edit time limit (e.g., 15 minutes)
            $editTimeLimit = 15 * 60; // 15 minutes in seconds
            if ($message->created_at->diffInSeconds(now()) > $editTimeLimit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message edit time limit exceeded'
                ], 400);
            }

            $message->update([
                'content' => $request->content,
                'is_edited' => true,
                'edited_at' => now()
            ]);

            $message->load([
                'user:id,first_name,last_name,avatar',
                'replyToMessage:id,content,user_id',
                'replyToMessage.user:id,first_name,last_name'
            ]);

            // Broadcast message update
            broadcast(new MessageSent($message))->toOthers();

            Log::info('✅ Enhanced message edited successfully', ['message_id' => $message->id]);

            return response()->json([
                'success' => true,
                'message' => 'Enhanced message edited successfully',
                'data' => [
                    'message' => $this->formatMessageResponse($message)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error editing enhanced message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to edit enhanced message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function destroy(Request $request, Chat $chat, Message $message): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('🗑️ Deleting enhanced message', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
                'user_id' => $user->id
            ]);

            // Check if user is the message sender or chat admin
            if ($message->user_id !== $user->id && $chat->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this message'
                ], 403);
            }

            // Check if message belongs to chat
            if ($message->chat_id !== $chat->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message does not belong to this chat'
                ], 404);
            }

            DB::beginTransaction();

            // Delete file if exists
            if ($message->file_url) {
                $this->deleteFile($message->file_url);
            }

            // Soft delete the message
            $message->update([
                'content' => 'This message was deleted',
                'type' => 'text',
                'is_deleted' => true,
                'deleted_at' => now(),
                'file_name' => null,
                'file_size' => null,
                'file_url' => null,
                'metadata' => []
            ]);

            DB::commit();

            // Broadcast message update
            broadcast(new MessageSent($message))->toOthers();

            Log::info('✅ Enhanced message deleted successfully', ['message_id' => $message->id]);

            return response()->json([
                'success' => true,
                'message' => 'Enhanced message deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error deleting enhanced message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete enhanced message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send typing indicator
     */
    public function typing(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'is_typing' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            Log::info('⌨️ Broadcasting enhanced typing indicator', [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'is_typing' => $request->is_typing
            ]);

            // Broadcast typing indicator
            broadcast(new UserTyping($chat, $user, $request->is_typing))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Enhanced typing indicator sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error sending enhanced typing indicator', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send enhanced typing indicator',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * React to a message
     */
    public function react(Request $request, Chat $chat, Message $message): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('😀 Reacting to enhanced message', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
                'user_id' => $user->id
            ]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            // Check if message belongs to chat
            if ($message->chat_id !== $chat->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message does not belong to this chat'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'reaction' => 'required|string|in:👍,❤️,😂,😮,😢,😡,👏,🔥'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $reaction = $request->reaction;
            $existingReaction = $message->reactions()
                ->where('user_id', $user->id)
                ->where('reaction', $reaction)
                ->first();

            $action = 'add';

            if ($existingReaction) {
                // Remove existing reaction
                $existingReaction->delete();
                $action = 'remove';
                Log::info('❌ Reaction removed', ['message_id' => $message->id, 'reaction' => $reaction]);
            } else {
                // Add new reaction
                $message->reactions()->create([
                    'user_id' => $user->id,
                    'reaction' => $reaction
                ]);
                Log::info('✅ Reaction added', ['message_id' => $message->id, 'reaction' => $reaction]);
            }

            DB::commit();

            // Broadcast reaction
            broadcast(new MessageReaction($message, $user, $reaction, $action))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Enhanced reaction sent successfully',
                'data' => [
                    'action' => $action,
                    'reaction' => $reaction
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error reacting to enhanced message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to react to enhanced message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search messages in a chat
     */
    public function search(Request $request, Chat $chat): JsonResponse
    {
        try {
            $user = Auth::user();
            Log::info('🔍 Searching enhanced messages', [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'query' => $request->get('q')
            ]);

            // Check if user is participant
            if (!$chat->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to chat'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'q' => 'required|string|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = $request->get('q');
            $limit = min($request->get('limit', 20), 50);

            $messages = Message::with([
                'user:id,first_name,last_name,avatar',
                'replyToMessage:id,content,user_id',
                'replyToMessage.user:id,first_name,last_name'
            ])
            ->where('chat_id', $chat->id)
            ->where('content', 'LIKE', "%{$query}%")
            ->where('is_deleted', false)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($message) {
                return $this->formatMessageResponse($message);
            });

            Log::info('✅ Enhanced message search completed', [
                'chat_id' => $chat->id,
                'query' => $query,
                'results_count' => $messages->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enhanced message search completed successfully',
                'data' => [
                    'messages' => $messages,
                    'query' => $query,
                    'total_results' => $messages->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error searching enhanced messages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search enhanced messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload(UploadedFile $file, string $messageType): array
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        
        // Determine upload path based on message type
        $uploadPath = match($messageType) {
            'image' => 'chat/images',
            'video' => 'chat/videos',
            'audio', 'voice' => 'chat/audio',
            default => 'chat/files'
        };

        // Store file
        $path = $file->storeAs($uploadPath, $fileName, 'public');
        $url = Storage::url($path);

        // Generate metadata based on file type
        $metadata = [
            'mime_type' => $mimeType,
            'original_name' => $file->getClientOriginalName()
        ];

        // Add image dimensions for images
        if (str_starts_with($mimeType, 'image/')) {
            try {
                $imagePath = Storage::disk('public')->path($path);
                $imageSize = getimagesize($imagePath);
                if ($imageSize) {
                    $metadata['width'] = $imageSize[0];
                    $metadata['height'] = $imageSize[1];
                }
            } catch (\Exception $e) {
                Log::warning('Could not get image dimensions', ['error' => $e->getMessage()]);
            }
        }

        Log::info('📎 File uploaded successfully', [
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'type' => $messageType,
            'path' => $path
        ]);

        return [
            'name' => $fileName,
            'size' => $fileSize,
            'url' => $url,
            'metadata' => $metadata
        ];
    }

    /**
     * Delete file from storage
     */
    private function deleteFile(string $fileUrl): void
    {
        try {
            $path = str_replace('/storage/', '', $fileUrl);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::info('🗑️ File deleted successfully', ['path' => $path]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete file', ['error' => $e->getMessage(), 'url' => $fileUrl]);
        }
    }

    /**
     * Format message response with enhanced details
     */
    private function formatMessageResponse(Message $message): array
    {
        $reactions = [];
        if ($message->relationLoaded('reactions')) {
            $reactionGroups = $message->reactions->groupBy('reaction');
            foreach ($reactionGroups as $reaction => $group) {
                $reactions[$reaction] = $group->count();
            }
        }

        return [
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'user_id' => $message->user_id,
            'type' => $message->type,
            'content' => $message->content,
            'file_name' => $message->file_name,
            'file_size' => $message->file_size,
            'file_url' => $message->file_url,
            'reply_to_message_id' => $message->reply_to_message_id,
            'is_edited' => $message->is_edited,
            'is_deleted' => $message->is_deleted,
            'reactions' => $reactions,
            'metadata' => $message->metadata ?? [],
            'created_at' => $message->created_at->toISOString(),
            'edited_at' => $message->edited_at?->toISOString(),
            'user' => [
                'id' => $message->user->id,
                'first_name' => $message->user->first_name,
                'last_name' => $message->user->last_name,
                'avatar' => $message->user->avatar
            ],
            'replyToMessage' => $message->replyToMessage ? [
                'id' => $message->replyToMessage->id,
                'content' => $message->replyToMessage->content,
                'user' => [
                    'id' => $message->replyToMessage->user->id,
                    'first_name' => $message->replyToMessage->user->first_name,
                    'last_name' => $message->replyToMessage->user->last_name
                ]
            ] : null
        ];
    }
}
