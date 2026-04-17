<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/SalesOrder.php
class SalesOrder extends Model
{
    protected $fillable = ['nomor_so', 'kantor_id', 'status', 'total', 'tanggal'];

    protected $casts = ['tanggal' => 'date'];

    public function kantor()
    {
        return $this->belongsTo(Kantor::class);
    }
}
