<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed contoh pertama: event "SO Dibuat" (so.created) → broadcast ke semua
 * user yang punya akses baca modul Fieldwork ('fieldworks'), sesuai kasus
 * yang dibahas user saat desain fitur ini. Admin bebas ubah/nonaktifkan dari
 * menu "Pengaturan Notifikasi" — ini cuma nilai awal, bukan sesuatu yang
 * dikunci.
 *
 * Catatan: event ini sebenarnya auto-register sendiri saat pertama kali
 * dipicu (lihat NotificationDispatcher::fire()) — migration ini cuma supaya
 * begitu dijalankan user, rule-nya sudah langsung aktif tanpa perlu dibuat
 * manual dulu dari menu.
 */
return new class extends Migration
{
    public function up(): void
    {
        $eventId = DB::table('notification_events')->insertGetId([
            'key'         => 'so.created',
            'label'       => 'SO Dibuat',
            'description' => 'Terpicu begitu Sales Order baru selesai dibuat.',
            'is_active'   => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('notification_rules')->insert([
            'notification_event_id' => $eventId,
            'recipient_type'        => 'permission',
            'permission_slug'       => 'fieldworks',
            'message_template'      => 'SO baru {{no_so}} dibuat oleh {{nama_user}}',
            'is_active'             => true,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public function down(): void
    {
        $eventId = DB::table('notification_events')->where('key', 'so.created')->value('id');
        if ($eventId) {
            DB::table('notification_rules')->where('notification_event_id', $eventId)->delete();
            DB::table('notification_events')->where('id', $eventId)->delete();
        }
    }
};
