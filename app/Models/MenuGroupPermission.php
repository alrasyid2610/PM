<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuGroupPermission extends Model
{
    protected $fillable = ['menu_group_id', 'menu_slug', 'can_read', 'can_create', 'can_update', 'can_delete'];
}
