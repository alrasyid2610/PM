<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PIC Pekerjaan SQ Work Order berpindah sumber (2026-10-25): dulu `business_relation_contacts.id_contact`
 * (kontak pelanggan), sekarang `users.id` (User Management) — sama seperti `work_orders`.
 * Nilai lama adalah id kontak, bukan id user, sehingga tidak bisa dipetakan otomatis dan akan
 * terbaca sebagai user yang salah. Dikosongkan; PIC dipilih ulang di tiap SQ Work Order.
 * Hanya mengosongkan kolom ini, tidak mengubah struktur tabel maupun data lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sq_work_orders')->whereNotNull('id_pic_pelanggan_pekerjaan')->update(['id_pic_pelanggan_pekerjaan' => null]);
    }

    public function down(): void
    {
        // Nilai lama (id kontak) tidak bisa dipulihkan.
    }
};
