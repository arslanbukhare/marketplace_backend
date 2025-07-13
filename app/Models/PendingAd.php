<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingAd extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'subcategory_id', 'title', 'description',
        'price', 'city', 'address', 'contact_number', 'show_contact_number',
        'featured_plan_id', 'dynamic_fields', 'images', 'status'
    ];

    protected $casts = [
        'dynamic_fields' => 'array',
        'images' => 'array',
        'show_contact_number' => 'boolean',
    ];

    public function appendFirstImageUrl()
    {
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $this->first_image_url = asset('storage/' . $this->images[0]->image_path);
        } else {
            $this->first_image_url = null;
        }

        // Optional: remove 'images' relationship from response
        unset($this->images);

        return $this;
    }

}
