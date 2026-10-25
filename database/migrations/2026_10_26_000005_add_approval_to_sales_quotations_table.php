<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Approval manager sebelum SQ Final (2026-10-26).
 * Alur: draft → pending (Menunggu Approval, terkunci) → final (disetujui) / kembali draft (ditolak, ada alasan).
 *  - diajukan_oleh / diajukan_at : siapa & kapan SQ diajukan untuk approval
 *  - approval_oleh / approval_at : pengambil keputusan terakhir (setujui atau tolak)
 *  - catatan_approval            : alasan penolakan (dikosongkan saat diajukan ulang / disetujui)
 * SQ Final yang sudah ada tidak berubah (kolom approval-nya kosong).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','pending','final','win','lose','void') NOT NULL DEFAULT 'draft'");

        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('diajukan_oleh')->nullable()->after('diputuskan_at');
            $table->timestamp('diajukan_at')->nullable()->after('diajukan_oleh');
            $table->unsignedBigInteger('approval_oleh')->nullable()->after('diajukan_at');
            $table->timestamp('approval_at')->nullable()->after('approval_oleh');
            $table->text('catatan_approval')->nullable()->after('approval_at');
        });
    }

    public function down(): void
    {
        // SQ yang sedang menunggu approval dikembalikan ke draft sebelum ENUM dipersempit
        DB::table('sales_quotations')->where('status', 'pending')->update(['status' => 'draft']);

        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropColumn(['diajukan_oleh', 'diajukan_at', 'approval_oleh', 'approval_at', 'catatan_approval']);
        });

        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','final','win','lose','void') NOT NULL DEFAULT 'draft'");
    }
};
