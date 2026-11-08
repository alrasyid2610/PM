<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lab Sample Reg: "Diterima Oleh" kini = user sistem (default user yang login), bukan master Personnel.
 * Kolom baru `id_user_penerima` (→ users.id). `id_personnel_penerima` DIPERTAHANKAN supaya pendaftaran lama tetap
 * menampilkan penerima lamanya (data lama tidak diubah); tampilan memakai COALESCE(user, personel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_lab_sample_regs', function (Blueprint $table) {
            $table->unsignedBigInteger('id_user_penerima')->nullable()->after('id_personnel_penerima')->index();
        });
    }

    public function down(): void
    {
        Schema::table('wo_lab_sample_regs', function (Blueprint $table) {
            $table->dropIndex(['id_user_penerima']);
            $table->dropColumn('id_user_penerima');
        });
    }
};
