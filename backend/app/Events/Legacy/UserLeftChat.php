<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLeftChat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;
    public $user;

    public function __construct(Chat $chat, User $user)
    {
        $this->chat = $chat;
        $this->user = $user;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chat->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user' => [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ],
            'chat_id' => $this->chat->id,
            'left_at' => now(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserLeftChat';
    }
}
