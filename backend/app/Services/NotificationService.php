<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Send a friend request notification
     */
    public static function sendFriendRequestNotification(User $sender, User $receiver)
    {
        return Notification::create([
            'user_id' => $receiver->id,
            'from_user_id' => $sender->id,
            'type' => 'friend_request',
            'title' => 'New Friend Request',
            'message' => "{$sender->first_name} {$sender->last_name} sent you a friend request",
            'data' => [
                'sender' => [
                    'id' => $sender->id,
                    'name' => "{$sender->first_name} {$sender->last_name}",
                    'avatar' => $sender->avatar_url,
                ],
                'action_url' => '/friends/requests',
            ],
        ]);
    }

    /**
     * Send a friend request accepted notification
     */
    public static function sendFriendRequestAcceptedNotification(User $accepter, User $requester)
    {
        return Notification::create([
            'user_id' => $requester->id,
            'from_user_id' => $accepter->id,
            'type' => 'friend_request_accepted',
            'title' => 'Friend Request Accepted',
            'message' => "{$accepter->first_name} {$accepter->last_name} accepted your friend request",
            'data' => [
                'accepter' => [
                    'id' => $accepter->id,
                    'name' => "{$accepter->first_name} {$accepter->last_name}",
                    'avatar' => $accepter->avatar_url,
                ],
                'action_url' => '/friends',
            ],
        ]);
    }

    /**
     * Send a new chat notification
     */
    public static function sendNewChatNotification(User $sender, User $receiver, $chatId)
    {
        return Notification::create([
            'user_id' => $receiver->id,
            'from_user_id' => $sender->id,
            'type' => 'new_chat',
            'title' => 'New Chat',
            'message' => "{$sender->first_name} {$sender->last_name} started a chat with you",
            'data' => [
                'sender' => [
                    'id' => $sender->id,
                    'name' => "{$sender->first_name} {$sender->last_name}",
                    'avatar' => $sender->avatar_url,
                ],
                'chat_id' => $chatId,
                'action_url' => "/chat?chatId={$chatId}",
            ],
        ]);
    }

    /**
     * Send a new message notification
     */
    public static function sendNewMessageNotification(User $sender, User $receiver, $chatId, $message)
    {
        return Notification::create([
            'user_id' => $receiver->id,
            'from_user_id' => $sender->id,
            'type' => 'new_message',
            'title' => 'New Message',
            'message' => "{$sender->first_name} {$sender->last_name}: " . substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''),
            'data' => [
                'sender' => [
                    'id' => $sender->id,
                    'name' => "{$sender->first_name} {$sender->last_name}",
                    'avatar' => $sender->avatar_url,
                ],
                'chat_id' => $chatId,
                'action_url' => "/chat?chatId={$chatId}",
            ],
        ]);
    }

    /**
     * Send a user blocked notification (to the user who got blocked)
     */
    public static function sendUserBlockedNotification(User $blocker, User $blocked)
    {
        return Notification::create([
            'user_id' => $blocked->id,
            'from_user_id' => $blocker->id,
            'type' => 'user_blocked',
            'title' => 'Account Update',
            'message' => "Your interaction with {$blocker->first_name} {$blocker->last_name} has been restricted",
            'data' => [
                'blocker' => [
                    'id' => $blocker->id,
                    'name' => "{$blocker->first_name} {$blocker->last_name}",
                ],
            ],
        ]);
    }

    /**
     * Delete notifications when users become friends (remove friend request notifications)
     */
    public static function cleanupFriendRequestNotifications(User $user1, User $user2)
    {
        // Remove friend request notifications between these users
        Notification::where(function($query) use ($user1, $user2) {
            $query->where('user_id', $user1->id)
                  ->where('from_user_id', $user2->id)
                  ->where('type', 'friend_request');
        })->orWhere(function($query) use ($user1, $user2) {
            $query->where('user_id', $user2->id)
                  ->where('from_user_id', $user1->id)
                  ->where('type', 'friend_request');
        })->delete();
    }
}
