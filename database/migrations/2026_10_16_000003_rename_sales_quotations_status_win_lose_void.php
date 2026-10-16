<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Status SQ: draft/final/cancel/completed → draft/final/win/lose/void (2026-10-16).
 * Pemetaan data lama: completed → win (SQ yang sudah jadi SO), cancel → void.
 * Lose hanya untuk data baru. ENUM diperluas dulu (lama + baru), data dipindah, lalu dipersempit.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','final','cancel','completed','win','lose','void') NOT NULL DEFAULT 'draft'");
        DB::table('sales_quotations')->where('status', 'completed')->update(['status' => 'win']);
        DB::table('sales_quotations')->where('status', 'cancel')->update(['status' => 'void']);
        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','final','win','lose','void') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','final','cancel','completed','win','lose','void') NOT NULL DEFAULT 'draft'");
        DB::table('sales_quotations')->where('status', 'win')->update(['status' => 'completed']);
        DB::table('sales_quotations')->whereIn('status', ['void', 'lose'])->update(['status' => 'cancel']);
        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','final','cancel','completed') NOT NULL DEFAULT 'draft'");
    }
};
