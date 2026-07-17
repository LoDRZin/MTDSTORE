<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'commission_rate',
        'balance',
        'min_withdrawal',
        'cookie_duration_days',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(AffiliateWithdrawal::class);
    }
}
