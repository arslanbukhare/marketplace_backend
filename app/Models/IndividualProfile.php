<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndividualProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'profile_picture',
        'verified_at',
    ];

    protected $casts = [
        'dob' => 'date',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
