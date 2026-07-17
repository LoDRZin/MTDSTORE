<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
