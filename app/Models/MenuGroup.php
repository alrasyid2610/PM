<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuGroup extends Model
{
    protected $fillable = ['name', 'description'];

    public function permissions()
    {
        return $this->hasMany(MenuGroupPermission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
