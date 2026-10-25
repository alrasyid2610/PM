<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    protected $fillable = [
        'notification_event_id', 'recipient_type', 'permission_slug',
        'message_template', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'notification_rule_recipients');
    }
}
