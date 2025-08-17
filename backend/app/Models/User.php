<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'gender',
        'profile_picture',
        'avatar',
        'bio_en',
        'bio_ar',
        'location',
        'timezone',
        'preferred_contact_method',
        'website',
        'linkedin',
        'github',
        'twitter',
        'years_of_experience',
        'education_level',
        'graduation_year',
        'privacy_settings',
        'user_type',
        'student_id',
        'university_id',
        'faculty_id',
        'major_id',
        'year_of_study',
        'gpa',
        'xp',
        'level',
        'languages',
        'preferred_language',
        'is_verified',
        'is_active',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
        'avatar_url',
        'profile_completion',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'languages' => 'array',
            'privacy_settings' => 'array',
            'last_active_at' => 'datetime',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'gpa' => 'decimal:2',
            'years_of_experience' => 'integer',
            'graduation_year' => 'integer',
        ];
    }

    // Relationships
    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function skills()
    {
        return $this->hasMany(UserSkill::class);
    }

    public function ownedSkills()
    {
        return $this->skills()->where('type', 'owned');
    }

    public function wantedSkills()
    {
        return $this->skills()->where('type', 'wanted');
    }

    public function skillSwapsAsRequester()
    {
        return $this->hasMany(SkillSwap::class, 'requester_id');
    }

    public function skillSwapsAsProvider()
    {
        return $this->hasMany(SkillSwap::class, 'provider_id');
    }

    public function mentorshipsAsMentor()
    {
        return $this->hasMany(Mentorship::class, 'mentor_id');
    }

    public function mentorshipsAsMentee()
    {
        return $this->hasMany(Mentorship::class, 'mentee_id');
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function chats()
    {
        return $this->belongsToMany(Chat::class, 'chat_user')
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

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'from_user_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function createdChats()
    {
        return $this->hasMany(Chat::class, 'created_by');
    }

    // Friendship relationships
    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withPivot('status', 'accepted_at')
            ->withTimestamps()
            ->unionAll(
                $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
                    ->wherePivot('status', 'accepted')
                    ->withPivot('status', 'accepted_at')
                    ->withTimestamps()
            );
    }

    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'blocked')
            ->withPivot('status')
            ->withTimestamps();
    }

    // Helper methods for friendship
    public function sendFriendRequest(User $user)
    {
        return Friendship::firstOrCreate([
            'user_id' => $this->id,
            'friend_id' => $user->id,
        ], [
            'status' => 'pending'
        ]);
    }

    public function acceptFriendRequest(User $user)
    {
        $friendship = Friendship::where('user_id', $user->id)
            ->where('friend_id', $this->id)
            ->where('status', 'pending')
            ->first();

        if ($friendship) {
            $friendship->accept();
            return true;
        }

        return false;
    }

    public function declineFriendRequest(User $user)
    {
        $friendship = Friendship::where('user_id', $user->id)
            ->where('friend_id', $this->id)
            ->where('status', 'pending')
            ->first();

        if ($friendship) {
            $friendship->decline();
            return true;
        }

        return false;
    }

    public function removeFriend(User $user)
    {
        Friendship::where(function($query) use ($user) {
            $query->where('user_id', $this->id)->where('friend_id', $user->id);
        })->orWhere(function($query) use ($user) {
            $query->where('user_id', $user->id)->where('friend_id', $this->id);
        })->delete();

        return true;
    }

    public function blockUser(User $user)
    {
        // Remove any existing friendship
        $this->removeFriend($user);

        // Create or update block relationship
        return Friendship::updateOrCreate([
            'user_id' => $this->id,
            'friend_id' => $user->id,
        ], [
            'status' => 'blocked'
        ]);
    }

    public function unblockUser(User $user)
    {
        return Friendship::where('user_id', $this->id)
            ->where('friend_id', $user->id)
            ->where('status', 'blocked')
            ->delete();
    }

    public function isFriendWith(User $user): bool
    {
        return Friendship::where(function($query) use ($user) {
            $query->where('user_id', $this->id)->where('friend_id', $user->id);
        })->orWhere(function($query) use ($user) {
            $query->where('user_id', $user->id)->where('friend_id', $this->id);
        })->where('status', 'accepted')->exists();
    }

    public function hasBlockedUser(User $user): bool
    {
        return Friendship::where('user_id', $this->id)
            ->where('friend_id', $user->id)
            ->where('status', 'blocked')
            ->exists();
    }

    public function hasFriendRequestFrom(User $user): bool
    {
        return Friendship::where('user_id', $user->id)
            ->where('friend_id', $this->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function hasSentFriendRequestTo(User $user): bool
    {
        return Friendship::where('user_id', $this->id)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return null;
        }
        return url('storage/' . $this->avatar);
    }

    public function getProfileCompletionAttribute()
    {
        $totalFields = 15; // Total number of profile fields we consider
        $completedFields = 0;

        // Basic profile fields (8 fields)
        if ($this->first_name) $completedFields++;
        if ($this->last_name) $completedFields++;
        if ($this->email) $completedFields++;
        if ($this->avatar) $completedFields++;
        if ($this->bio_en) $completedFields++;
        if ($this->phone) $completedFields++;
        if ($this->date_of_birth) $completedFields++;
        if ($this->gender) $completedFields++;

        // Academic fields (4 fields)
        if ($this->university_id) $completedFields++;
        if ($this->faculty_id) $completedFields++;
        if ($this->major_id) $completedFields++;
        if ($this->year_of_study) $completedFields++;

        // Additional fields (3 fields)
        if ($this->location) $completedFields++;
        if ($this->years_of_experience !== null) $completedFields++;
        if ($this->graduation_year) $completedFields++;

        return round(($completedFields / $totalFields) * 100);
    }

    public function isMentor()
    {
        return $this->user_type === 'mentor' || $this->profile?->is_mentor;
    }

    public function updateXp($points)
    {
        $this->increment('xp', $points);
        $this->updateLevel();
    }

    private function updateLevel()
    {
        $newLevel = floor($this->xp / 1000) + 1; // Every 1000 XP = 1 level
        if ($newLevel > $this->level) {
            $this->update(['level' => $newLevel]);
        }
    }
}
