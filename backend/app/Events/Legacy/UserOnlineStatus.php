<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnlineStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $chatId;
    public $isOnline;

    public function __construct(User $user, int $chatId, bool $isOnline = true)
    {
        $this->user = $user;
        $this->chatId = $chatId;
        $this->isOnline = $isOnline;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'avatar' => $this->user->avatar,
            ],
            'chat_id' => $this->chatId,
            'is_online' => $this->isOnline,
            'last_seen' => $this->isOnline ? now() : $this->user->last_active_at,
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserOnlineStatus';
    }
}
