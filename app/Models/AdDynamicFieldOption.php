<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdDynamicFieldOption extends Model
{
    protected $fillable = ['field_id', 'value'];

    public function field()
    {
        return $this->belongsTo(AdDynamicField::class, 'field_id');
    }

}
