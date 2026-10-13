<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. `users.no_hp` — nomor HP user (dipakai poin keterangan "Kontak Informasi" di SQ).
 * 2. Tipe isian poin keterangan `personel` → `user` (kontak sekarang dipilih dari User
 *    Management, bukan Personnel). Template di master diganti, dan nilai pada SQ Draft
 *    yang sudah memilih personel dipetakan lewat `personnel.id_user`; personel yang
 *    tidak punya akun user dikosongkan (isian kembali "belum lengkap").
 *    SQ Final aman — teksnya sudah terkunci di `sq_keterangan.teks_final`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('no_hp', 30)->nullable()->after('email');
        });

        $poin = DB::table('poin_keterangan')->where('template', 'like', '%:personel%')->get();
        foreach ($poin as $p) {
            $keys = [];
            if (preg_match_all('/\{([a-z0-9_]+):personel\}/', $p->template, $m)) $keys = $m[1];

            DB::table('poin_keterangan')->where('id_poin', $p->id_poin)->update([
                'template'   => preg_replace('/:personel\}/', ':user}', $p->template),
                'updated_at' => now(),
            ]);

            foreach (DB::table('sq_keterangan')->where('id_poin', $p->id_poin)->whereNotNull('nilai')->get() as $k) {
                $nilai = json_decode($k->nilai, true) ?: [];
                foreach ($keys as $key) {
                    if (!isset($nilai[$key])) continue;
                    $idUser = DB::table('personnel')->where('id_personnel', $nilai[$key])->value('id_user');
                    if ($idUser) $nilai[$key] = (int) $idUser; else unset($nilai[$key]);
                }
                DB::table('sq_keterangan')->where('id_sq_keterangan', $k->id_sq_keterangan)
                    ->update(['nilai' => $nilai ? json_encode($nilai) : null]);
            }
        }
    }

    public function down(): void
    {
        // Pemetaan nilai personel↔user tidak bisa dibalik dengan aman; hanya kolom yang dihapus.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('no_hp');
        });
    }
};
