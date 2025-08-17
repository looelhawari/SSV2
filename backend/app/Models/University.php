<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class University extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name_en',
        'name_ar',
        'code',
        'type',
        'city',
        'logo',
        'description_en',
        'description_ar',
        'website',
        'contact_info',
        'is_active',
        'student_count',
    ];

    protected $casts = [
        'contact_info' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function faculties()
    {
        return $this->hasMany(Faculty::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
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
}
