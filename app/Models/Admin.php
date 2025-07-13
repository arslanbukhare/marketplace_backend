<?php

namespace App\Models;
use Filament\Panel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{

    protected $fillable = ['name', 'email', 'password'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true; 
    }

}


