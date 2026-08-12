<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Master data Satuan (dipakai di field "Satuan" pada BOQ, sebelumnya
 * hardcode PCS/Titik/Set di frontend). Collation di-set eksplisit
 * utf8mb4_general_ci supaya sama dengan tabel lama (boq dkk) — default
 * Laravel (utf8mb4_unicode_ci) akan bentrok saat JOIN/UPDATE lintas tabel
 * ("Illegal mix of collations"), lihat migration 2026_08_12_000001.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satuan', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_satuan');
            $table->string('nama', 50)->unique();
            $table->boolean('is_aktif')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        DB::table('satuan')->insert(collect([
            'PCS', 'Titik', 'Set',
        ])->map(fn($nama) => ['nama' => $nama, 'created_at' => $now, 'updated_at' => $now])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('satuan');
    }
};
