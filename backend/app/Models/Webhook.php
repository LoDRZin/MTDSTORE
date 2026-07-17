<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'events',
        'secret',
        'active',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }
}
