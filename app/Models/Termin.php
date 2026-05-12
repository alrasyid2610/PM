<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Termin extends Model
{
    protected $table = 'termin';
    protected $primaryKey = 'id_termin';

    protected $fillable = [
        'nomor',
        'nama',
        'persentase',
        'nilai',
        'tanggal',
        'status',
        'keterangan',
        'attachment',
    ];

    protected $casts = [
        'tanggal'    => 'date',
        'attachment' => 'array',
    ];
}
