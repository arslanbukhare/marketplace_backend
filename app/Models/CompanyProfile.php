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
        'registration_document_status', 
        'registration_expiry_date',    
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'description',
        'logo',
        'contact_phone',
        'is_contact_phone_verified',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'registration_expiry_date' => 'date', // ✅ Optional, helps with date parsing
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
