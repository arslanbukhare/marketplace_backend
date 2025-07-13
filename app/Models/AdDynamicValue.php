<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdDynamicValue extends Model
{
    protected $fillable = ['ad_id', 'field_id', 'value'];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function field()
    {
        return $this->belongsTo(AdDynamicField::class, 'field_id');
    }
}
