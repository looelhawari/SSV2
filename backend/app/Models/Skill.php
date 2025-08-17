<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Laravel\Scout\Searchable;

class Skill extends Model
{
    use HasFactory; // Temporarily disable Searchable

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'description_en',
        'description_ar',
        'category',
        'difficulty_level',
        'prerequisites',
        'tags',
        'icon',
        'popularity_score',
        'is_verified',
        'is_active',
    ];

    protected $casts = [
        'prerequisites' => 'array',
        'tags' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function userSkills()
    {
        return $this->hasMany(UserSkill::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills')->withPivot('type', 'proficiency_level', 'years_of_experience');
    }

    public function ownedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_skills')->wherePivot('type', 'owned');
    }

    public function wantedByUsers()
    {
        return $this->belongsToMany(User::class, 'user_skills')->wherePivot('type', 'wanted');
    }

    public function skillSwapsRequested()
    {
        return $this->hasMany(SkillSwap::class, 'requested_skill_id');
    }

    public function skillSwapsOffered()
    {
        return $this->hasMany(SkillSwap::class, 'offered_skill_id');
    }

    public function mentorships()
    {
        return $this->hasMany(Mentorship::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    // Helper methods
    public function getName($locale = 'en')
    {
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getDescription($locale = 'en')
    {
        return $locale === 'ar' ? $this->description_ar : $this->description_en;
    }

    // Temporarily commented out Scout methods
    /*
    public function searchableAs()
    {
        return 'skills_index';
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'category' => $this->category,
            'difficulty_level' => $this->difficulty_level,
            'tags' => $this->tags,
        ];
    }
    */
}
