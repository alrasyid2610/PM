<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Urutan kategori Budget Account (hanya dipakai kategori/parent) — menentukan urutan section
 * di modal Tambah Budget Plan (template kategori) dan urutan saat "+ Kategori" ditambahkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->default(0)->after('kode');
        });

        // Urutan awal mengikuti template Excel; kode yang tidak ditemukan dilewati.
        // Kategori lain (mis. dibuat belakangan) mendapat urutan setelahnya, berurut id.
        $kodeUrut = ['SEWA', 'TRANSPORT', 'UM', 'HONOR', 'PENGINAPAN', 'LAINNYA'];
        $n = 0;
        foreach ($kodeUrut as $kode) {
            $n++;
            DB::table('budget_accounts')->whereNull('id_parent')->where('kode', $kode)->update(['urutan' => $n]);
        }
        $sisa = DB::table('budget_accounts')->whereNull('id_parent')->whereNull('deleted_at')
            ->where('urutan', 0)->orderBy('id_account')->pluck('id_account');
        foreach ($sisa as $id) {
            DB::table('budget_accounts')->where('id_account', $id)->update(['urutan' => ++$n]);
        }
    }

    public function down(): void
    {
        Schema::table('budget_accounts', function (Blueprint $table) {
            $table->dropColumn('urutan');
        });
    }
};
