<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Chat extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'avatar',
        'created_by',
        'last_message_at',
        'is_active',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'avatar_url',
        'unread_count',
    ];

    // Relationships
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_user')
            ->withPivot([
                'role', 
                'joined_at', 
                'last_seen_at', 
                'last_read_message_id',
                'is_muted',
                'is_pinned',
                'settings'
            ])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }
        return url('storage/' . $this->avatar);
    }

    public function getUnreadCountAttribute(): int
    {
        $user = Auth::user();
        if (!$user) return 0;

        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;
        if (!$pivot) return 0;

        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->where('id', '>', $pivot->last_read_message_id ?? 0)
            ->count();
    }

    // Helper methods
    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function getDisplayName(User $forUser = null): string
    {
        if ($this->isGroup()) {
            return $this->name ?? 'Group Chat';
        }

        // For private chats, return the other participant's name
        $forUser = $forUser ?? Auth::user();
        if (!$forUser) return 'Private Chat';

        $otherUser = $this->users()->where('user_id', '!=', $forUser->id)->first();
        return $otherUser ? $otherUser->full_name : 'Private Chat';
    }

    public function addParticipant(User $user, string $role = 'member'): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'joined_at' => now(),
            ]
        ]);
    }

    public function removeParticipant(User $user): void
    {
        $this->users()->detach($user->id);
    }

    public function markAsRead(User $user, int $messageId): void
    {
        $this->users()->updateExistingPivot($user->id, [
            'last_read_message_id' => $messageId,
            'last_seen_at' => now(),
        ]);
    }

    // Scopes
    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
