<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/Kantor.php
class Kantor extends Model
{
    protected $fillable = ['nama', 'tipe', 'alamat'];

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }
}
