<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Urutan tampil Budget Account (2026-10-26). Dipakai untuk mengurutkan Kategori (parent) dan
 * Sub Kategori (child di dalam satu parent) di modal Budget Plan yang dikelompokkan per kategori.
 * Dibuat kolom sendiri — urutan tidak mengikuti kode/nama account.
 * Data lama diisi berurutan sesuai urutan tampil sekarang (nama A-Z), jadi tidak ada yang berubah
 * sampai user mengatur ulang di Budget Account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->default(0)->after('kode');
        });

        $parents = DB::table('budget_accounts')->whereNull('id_parent')->orderBy('nama')->pluck('id_account');
        foreach ($parents as $i => $id) {
            DB::table('budget_accounts')->where('id_account', $id)->update(['urutan' => $i + 1]);

            $children = DB::table('budget_accounts')->where('id_parent', $id)->orderBy('nama')->pluck('id_account');
            foreach ($children as $j => $childId) {
                DB::table('budget_accounts')->where('id_account', $childId)->update(['urutan' => $j + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }
};
