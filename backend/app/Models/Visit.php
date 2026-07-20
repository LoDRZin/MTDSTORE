<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visit extends Model
{
    use HasFactory;

    public $timestamps = false; // Como só tem visited_at, melhor desativar timestamps padrão se não existir created_at

    protected $fillable = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'session_id',
        'customer_id',
        'visited_at',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
