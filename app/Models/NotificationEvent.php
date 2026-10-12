<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationEvent extends Model
{
    protected $fillable = ['key', 'label', 'description', 'is_active', 'available_fields'];

    protected $casts = [
        'is_active'        => 'boolean',
        'available_fields' => 'array',
    ];

    public function rules()
    {
        return $this->hasMany(NotificationRule::class);
    }
}
