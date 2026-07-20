<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'author',
        'content',
        'cover_image',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            if (! in_array($post->status, ['draft', 'published'], true)) {
                throw new \InvalidArgumentException('Status de postagem invÃ¡lido.');
            }
        });
    }
}
