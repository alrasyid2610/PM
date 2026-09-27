<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Status SQ disederhanakan (2026-09-27): draft/terkirim/diterima/ditolak/
 * cancel/expired → draft/final/cancel/completed.
 *
 * Tanpa pemetaan data lama: tabel SQ sudah di-truncate sebelum migration
 * ini (data uji), jadi cukup mengganti definisi enum. Jangan jalankan di
 * environment yang sudah punya SQ berstatus lama — ALTER akan gagal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','final','cancel','completed') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sales_quotations MODIFY status ENUM('draft','terkirim','diterima','ditolak','cancel','expired') NOT NULL DEFAULT 'draft'");
    }
};
