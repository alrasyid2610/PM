<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Kotak masuk notifikasi per user — 1 baris per penerima.
 * Soft delete di sini artinya "disembunyikan dari daftar user ybs", bukan
 * dihapus dari histori event secara keseluruhan.
 */
class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'event_key', 'title', 'body', 'url', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
