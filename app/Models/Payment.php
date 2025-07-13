<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'ad_id',
        'amount',
        'currency',
        'payment_method',
        'payment_status',
        'expires_at',
        'stripe_session_id',
        'paid_at',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
