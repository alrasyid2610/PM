<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class KantorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kantor::insert([
            ['nama' => 'Kantor Pusat Jakarta', 'tipe' => 'pusat', 'alamat' => 'Jakarta', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Kantor Pusat Surabaya', 'tipe' => 'pusat', 'alamat' => 'Surabaya', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Cabang Bandung', 'tipe' => 'cabang', 'alamat' => 'Bandung', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Cabang Medan', 'tipe' => 'cabang', 'alamat' => 'Medan', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Cabang Bali', 'tipe' => 'cabang', 'alamat' => 'Bali', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
