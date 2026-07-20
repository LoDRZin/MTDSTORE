<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReviewSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'auto_publish',
        'suggested_phrases',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_publish' => 'boolean',
        'suggested_phrases' => 'array',
    ];
}
