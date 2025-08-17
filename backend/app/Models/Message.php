<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'user_id',
        'type',
        'content',
        'file_name',
        'file_size',
        'mime_type',
        'metadata',
        'reply_to_message_id',
        'is_edited',
        'edited_at',
        'read_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_by' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    protected $appends = [
        'file_url',
        'is_read',
    ];

    // Relationships
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyToMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->content || $this->type === 'text') {
            return null;
        }
        return url('storage/' . $this->content);
    }

    public function getIsReadAttribute(): bool
    {
        $user = Auth::user();
        if (!$user || $user->id === $this->user_id) {
            return true; // Sender always sees their messages as read
        }

        $readBy = $this->read_by ?? [];
        return in_array($user->id, $readBy);
    }

    // Helper methods
    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isFile(): bool
    {
        return in_array($this->type, ['image', 'file', 'audio', 'video']);
    }

    public function markAsReadBy(User $user): void
    {
        $readBy = $this->read_by ?? [];
        
        if (!in_array($user->id, $readBy)) {
            $readBy[] = $user->id;
            $this->update(['read_by' => $readBy]);
        }
    }

    public function getFormattedContent(): string
    {
        switch ($this->type) {
            case 'text':
                return $this->content;
            case 'image':
                return '📷 Image';
            case 'file':
                return '📎 ' . ($this->file_name ?? 'File');
            case 'audio':
                return '🎵 Audio';
            case 'video':
                return '🎥 Video';
            default:
                return $this->content;
        }
    }

    // Scopes
    public function scopeForChat($query, Chat $chat)
    {
        return $query->where('chat_id', $chat->id);
    }

    public function scopeUnreadBy($query, User $user)
    {
        return $query->where('user_id', '!=', $user->id)
            ->whereJsonDoesntContain('read_by', $user->id);
    }
}
