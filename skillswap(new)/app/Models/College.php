<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class College extends Model
{
    use HasFactory;

    protected $primaryKey = 'college_id';
    protected $table = 'colleges';

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'image_url',
    ];

    // Relationships
    public function majors(): HasMany
    {
        return $this->hasMany(Major::class, 'college_id', 'college_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'college_id', 'college_id');
    }
}
