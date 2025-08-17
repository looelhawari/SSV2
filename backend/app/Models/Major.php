<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    protected $fillable = [
        'faculty_id',
        'name_en',
        'name_ar',
        'code',
        'description_en',
        'description_ar',
        'degree_type',
        'duration_years',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getName($locale = 'en')
    {
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }
}
