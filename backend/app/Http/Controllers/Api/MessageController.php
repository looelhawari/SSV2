<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\UserTyping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Get messages for a chat (with pagination)
     */
    public function index(Request $request, $chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($chatId);
            
            $page = $request->get('page', 1);
            $limit = $request->get('limit', 50);
            
            $messages = Message::forChat($chat)
                ->with([
                    'user:id,first_name,last_name,avatar',
                    'replyToMessage.user:id,first_name,last_name'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            // Mark messages as read
            $latestMessage = $messages->items()[0] ?? null;
            if ($latestMessage) {
                $chat->markAsRead($user, $latestMessage->id);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'messages' => array_reverse($messages->items()), // Reverse to show oldest first
                    'pagination' => [
                        'current_page' => $messages->currentPage(),
                        'last_page' => $messages->lastPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total(),
                        'has_more' => $messages->hasMorePages()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a new message
     */
    public function store(Request $request, $chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($chatId);

            $validator = Validator::make($request->all(), [
                'content' => 'required_without:file|string|max:5000',
                'type' => 'in:text,image,file,audio,video',
                'file' => 'file|max:10240', // 10MB max
                'reply_to_message_id' => 'nullable|exists:messages,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $messageData = [
                'chat_id' => $chat->id,
                'user_id' => $user->id,
                'type' => $request->type ?? 'text',
                'reply_to_message_id' => $request->reply_to_message_id,
            ];

            // Handle file uploads
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                
                // Determine message type based on file
                $mimeType = $file->getMimeType();
                if (str_starts_with($mimeType, 'image/')) {
                    $messageData['type'] = 'image';
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $messageData['type'] = 'audio';
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $messageData['type'] = 'video';
                } else {
                    $messageData['type'] = 'file';
                }

                // Store file
                $filename = 'chat-files/' . $chat->id . '/' . time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('', $filename, 'public');
                
                $messageData['content'] = $path;
                $messageData['file_name'] = $file->getClientOriginalName();
                $messageData['file_size'] = $file->getSize();
                $messageData['mime_type'] = $mimeType;
                
                // Add metadata for images/videos
                if (in_array($messageData['type'], ['image', 'video'])) {
                    $messageData['metadata'] = [
                        'size' => $file->getSize(),
                        'original_name' => $file->getClientOriginalName(),
                    ];
                }
            } else {
                $messageData['content'] = $request->content;
            }

            // Create message
            $message = Message::create($messageData);
            
            // Update chat's last message timestamp
            $chat->update(['last_message_at' => now()]);

            // Load the message with relationships
            $message->load(['user:id,first_name,last_name,avatar', 'replyToMessage.user:id,first_name,last_name']);

            // Broadcast message to chat participants with logging for debug
            try {
                Log::info('Broadcast driver in use', [ 'driver' => config('broadcasting.default') ]);
                Log::info('Broadcasting MessageSent', [
                    'chat_id' => $chat->id,
                    'message_id' => $message->id,
                    'user_id' => $user->id
                ]);
                broadcast(new MessageSent($message))->toOthers();
            } catch (\Throwable $broadcastException) {
                Log::error('Broadcast failed', [
                    'error' => $broadcastException->getMessage(),
                    'chat_id' => $chat->id,
                    'message_id' => $message->id
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent successfully',
                'data' => ['message' => $message]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a message (edit)
     */
    public function update(Request $request, $chatId, $messageId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($chatId);
            $message = Message::forChat($chat)->where('user_id', $user->id)->findOrFail($messageId);

            // Only allow editing text messages
            if ($message->type !== 'text') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only text messages can be edited'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:5000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $message->update([
                'content' => $request->content,
                'is_edited' => true,
                'edited_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Message updated successfully',
                'data' => ['message' => $message->fresh()]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function destroy($chatId, $messageId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($chatId);
            $message = Message::forChat($chat)->where('user_id', $user->id)->findOrFail($messageId);

            // Delete associated file if exists
            if ($message->isFile() && $message->content) {
                Storage::disk('public')->delete($message->content);
            }

            $message->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Message deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request, $chatId, $messageId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($chatId);
            $message = Message::forChat($chat)->findOrFail($messageId);

            $message->markAsReadBy($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Message marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark message as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search messages in a chat
     */
    public function search(Request $request, $chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($chatId);

            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $messages = Message::forChat($chat)
                ->where('type', 'text')
                ->where('content', 'like', '%' . $request->query . '%')
                ->with('user:id,first_name,last_name,avatar')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'messages' => $messages,
                    'count' => $messages->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle typing indicator
     */
    public function typing(Request $request, $chatId)
    {
        try {
            $user = Auth::user();
            
            $chat = Chat::forUser($user)->findOrFail($chatId);

            $validator = Validator::make($request->all(), [
                'is_typing' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Broadcast typing indicator with logging
            try {
                Log::info('Broadcasting UserTyping', [
                    'chat_id' => $chat->id,
                    'user_id' => $user->id,
                    'is_typing' => $request->is_typing
                ]);
                broadcast(new UserTyping($user, $chat->id, $request->is_typing))->toOthers();
            } catch (\Throwable $broadcastException) {
                Log::error('Broadcast typing failed', [
                    'error' => $broadcastException->getMessage(),
                    'chat_id' => $chat->id,
                    'user_id' => $user->id
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Typing indicator sent'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send typing indicator',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
