<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'industry',
        'registration_number',
        'registration_document',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'description',
        'logo',
        'contact_phone',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
