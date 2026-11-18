<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mekanisme approval Testing Point dan Paket Testing Point (pola sama seperti approval SQ).
 *  - status_approval : draft | pending | approved. Hanya yang `approved` boleh dipakai di BOQ baru.
 *  - diajukan_oleh/at, approval_oleh/at, catatan_approval : jejak pengajuan & keputusan (catatan = alasan tolak).
 *
 * Data lama: SEMUA baris yang sudah ada otomatis `approved`, supaya tidak ada Testing Point/Paket yang tiba-tiba
 * hilang dari pilihan BOQ. Baris baru (default kolom `draft`) harus diajukan & disetujui dulu.
 * Kolom terpisah dari `is_aktif` (aktif/nonaktif = boleh dipakai atau tidak; approval = sudah diverifikasi).
 */
return new class extends Migration
{
    private array $tables = ['testing_points', 'testing_point_paket'];

    public function up(): void
    {
        foreach ($this->tables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->string('status_approval', 20)->default('draft')->index();
                $table->unsignedBigInteger('diajukan_oleh')->nullable();
                $table->timestamp('diajukan_at')->nullable();
                $table->unsignedBigInteger('approval_oleh')->nullable();
                $table->timestamp('approval_at')->nullable();
                $table->text('catatan_approval')->nullable();
            });

            // Data lama (termasuk yang soft-delete) dianggap sudah disetujui
            DB::table($tbl)->update(['status_approval' => 'approved']);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                $table->dropIndex($tbl . '_status_approval_index');
                $table->dropColumn(['status_approval', 'diajukan_oleh', 'diajukan_at', 'approval_oleh', 'approval_at', 'catatan_approval']);
            });
        }
    }
};
