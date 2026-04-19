<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalesOrderSeeder extends Seeder
{
$statuses = ['draft', 'pending', 'approved', 'rejected', 'completed'];
    $kantorIds = \App\Models\Kantor::pluck('id')->toArray();

    for ($i = 1; $i <= 120; $i++) {
        \App\Models\SalesOrder::create([
            'nomor_so'  => 'SO-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            'kantor_id' => $kantorIds[array_rand($kantorIds)],
            'status'    => $statuses[array_rand($statuses)],
            'total'     => rand(1000000, 50000000),
            'tanggal'   => now()->subDays(rand(0, 365))->format('Y-m-d'),
        ]);
    }
}
