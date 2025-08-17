<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $fillable = [
        'university_id',
        'name_en',
        'name_ar',
        'code',
        'description_en',
        'description_ar',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
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
