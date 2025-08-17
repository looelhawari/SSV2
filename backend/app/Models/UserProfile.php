<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio_en',
        'bio_ar',
        'interests',
        'career_goals',
        'social_links',
        'availability_status',
        'is_mentor',
        'hourly_rate',
        'rating',
        'total_reviews',
        'total_sessions_given',
        'location',
        'timezone',
        'preferred_contact_method',
        'languages_spoken',
        'years_of_experience',
        'education_level',
        'certifications',
        'portfolio_links',
    ];

    protected $casts = [
        'interests' => 'array',
        'career_goals' => 'array',
        'social_links' => 'array',
        'languages_spoken' => 'array',
        'certifications' => 'array',
        'portfolio_links' => 'array',
        'is_mentor' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'rating' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
