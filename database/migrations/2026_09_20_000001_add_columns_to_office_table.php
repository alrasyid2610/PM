<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tabel `office` sudah ada sebelumnya (dibuat manual, cuma id_office + name,
// belum tercatat migration) — dipakai SalesOrderController tapi belum pernah
// punya modul CRUD sendiri (dropdown Office di SO masih hardcode 2 opsi).
// Migration ini menambah kolom yang kurang (konsisten dengan pola soft-delete
// modul lain) + seed 2 data yang sudah dipakai (hardcode) selama ini supaya
// id_office 1/2 di data SO yang sudah ada tetap valid.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office', function (Blueprint $table) {
            $table->string('alamat')->nullable()->after('name');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('office')->insert([
            ['id_office' => 1, 'name' => 'Pramatek Jakarta', 'alamat' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id_office' => 2, 'name' => 'Pramatek Bandung', 'alamat' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::table('office', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'created_at', 'updated_at', 'deleted_at']);
        });
    }
};
