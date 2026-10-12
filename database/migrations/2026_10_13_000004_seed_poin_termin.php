<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Varian poin "Termin Pembayaran" (contoh penawaran no. 1: DP 40% + 30% + 30%).
 * Isian bertipe `termin` = daftar tahap (persen + pemicu pilihan tetap), total wajib 100%.
 */
return new class extends Migration
{
    private const TEMPLATE = 'Termin Pembayaran :{jadwal:termin}';

    public function up(): void
    {
        $ada = DB::table('poin_keterangan')->where('template', self::TEMPLATE)->whereNull('deleted_at')->exists();
        if ($ada) return;

        DB::table('poin_keterangan')->insert([
            'kategori'       => 'termin',
            'template'       => self::TEMPLATE,
            'is_default'     => 0,
            'urutan_default' => 0,
            'is_aktif'       => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('poin_keterangan')->where('template', self::TEMPLATE)->delete();
    }
};
