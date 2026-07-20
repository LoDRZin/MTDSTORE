<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'response_status',
        'response_body',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
