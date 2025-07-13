<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedAdPlan extends Model
{
    protected $fillable = ['name', 'price', 'duration_days', 'stripe_price_id', 'currency'];
}
