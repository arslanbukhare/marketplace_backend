<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'has_dynamic_fields','icon'];

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    public function dynamicFields()
    {
        return $this->hasMany(AdDynamicField::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class);
    }
}
