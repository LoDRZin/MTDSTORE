<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'amount',
        'status',
        'admin_notes',
    ];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
}
