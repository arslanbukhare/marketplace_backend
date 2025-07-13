<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Ad extends Model
{

    use Searchable;
    protected $with = ['category', 'subcategory', 'dynamicValues'];

    protected $fillable = [
    'user_id', 'user_type', 'category_id', 'subcategory_id', 'title', 'description', 'price',
    'city', 'address', 'contact_number', 'show_contact_number', 'is_featured', 'featured_expires_at',
    'status', 'is_affiliate', 'affiliate_url', 'affiliate_source', 'click_count'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function images()
    {
        return $this->hasMany(AdImage::class);
    }

    public function dynamicValues()
    {
        return $this->hasMany(AdDynamicValue::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

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

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'city' => $this->city,
            'category_id' => $this->category_id,   // ✅ ADD THIS
            'subcategory_id' => $this->subcategory_id,
            'category' => optional($this->category)->name,
            'subcategory' => optional($this->subcategory)->name,
            'dynamic_values' => $this->dynamicValues->pluck('value')->toArray(),
        ];
    }


}
