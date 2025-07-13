<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdDynamicField extends Model
{
    protected $fillable = ['category_id', 'field_name', 'field_type', 'is_required'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function values()
    {
        return $this->hasMany(AdDynamicValue::class, 'field_id');
    }

    public function options()
    {
        return $this->hasMany(AdDynamicFieldOption::class, 'field_id');
    }
}
