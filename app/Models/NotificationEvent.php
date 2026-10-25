<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEvent extends Model
{
    protected $fillable = ['key', 'label', 'description', 'module', 'is_active', 'is_enabled', 'available_fields', 'available_roles'];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_enabled'       => 'boolean',
        'available_fields' => 'array',
        'available_roles'  => 'array',
    ];

    public function rules()
    {
        return $this->hasMany(NotificationRule::class);
    }
}
