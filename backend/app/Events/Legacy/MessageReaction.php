<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReaction implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user;
    public $reaction;
    public $action; // 'add' or 'remove'

    public function __construct(Message $message, User $user, string $reaction, string $action = 'add')
    {
        $this->message = $message;
        $this->user = $user;
        $this->reaction = $reaction;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->chat_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'user' => [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
            ],
            'reaction' => $this->reaction,
            'action' => $this->action,
            'chat_id' => $this->message->chat_id,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageReaction';
    }
}
