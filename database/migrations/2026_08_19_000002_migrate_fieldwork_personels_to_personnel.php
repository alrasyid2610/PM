<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Pindahkan referensi Personel FWO dari `users` ke `personnel` baru.
 * Data users yang pernah dipakai sebagai personel FWO (PIC/Sampling/dst,
 * bukan akun sistem sungguhan — dikonfirmasi user 2026-08-19) di-copy jadi
 * baris `personnel` baru, TIDAK di-link ke id_user (personnel hasil migrasi
 * ini tetap tidak bisa login, sesuai desain awal). Baris asli di tabel
 * `users` tidak dihapus/diubah — supaya tidak ada dampak ke data lain
 * (audit_logs, dsb) yang mungkin masih mereferensikan user id tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fieldwork_personels', function (Blueprint $table) {
            $table->unsignedInteger('id_personnel')->nullable()->after('id_user');
        });

        $userIds = DB::table('fieldwork_personels')->whereNotNull('id_user')->distinct()->pluck('id_user');

        $mapping = [];
        foreach ($userIds as $userId) {
            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user) continue;

            $mapping[$userId] = DB::table('personnel')->insertGetId([
                'nama'       => $user->name,
                'is_aktif'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id_personnel');
        }

        foreach ($mapping as $userId => $personnelId) {
            DB::table('fieldwork_personels')->where('id_user', $userId)->update(['id_personnel' => $personnelId]);
        }

        Schema::table('fieldwork_personels', function (Blueprint $table) {
            $table->dropColumn('id_user');
        });
    }

    public function down(): void
    {
        Schema::table('fieldwork_personels', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user')->nullable()->after('id_fwo');
        });

        Schema::table('fieldwork_personels', function (Blueprint $table) {
            $table->dropColumn('id_personnel');
        });
    }
};
