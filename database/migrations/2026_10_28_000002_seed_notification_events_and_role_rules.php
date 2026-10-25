<?php

use App\Support\NotificationCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed pola segregasi notifikasi (disusun bersama user 2026-10-28): 19 event master ditambahkan ke daftar kelola,
 * masing-masing diberi 1 rule "Peran dari data" (hanya orang yang terkait record) + template pesan.
 * Rule "User tertentu" (manajer/approver/finance/verifikator) sengaja TIDAK di-seed — diisi admin lewat menu.
 *
 * Idempoten & tidak menimpa: event yang sudah ada hanya di-enable; rule peran ditambahkan hanya kalau event
 * itu belum punya rule bertipe 'roles'. Rule lain buatan admin tidak disentuh.
 */
return new class extends Migration
{
    private const SQ_ROLES = ['pic_input', 'marketing_internal'];

    private function plan(): array
    {
        $so  = ['pic_input', 'pic_order', 'marketing_internal'];
        $wo  = ['wo_pic', 'pic_order', 'marketing_internal'];
        $fwo = ['fwo_penyelia', 'fwo_personel', 'wo_pic'];
        $out = ['wo_pic', 'pic_order'];

        return [
            'sq.created'    => [self::SQ_ROLES, 'SQ baru {{no_sq}} dibuat oleh {{nama_user}}, dengan judul order "{{judul_order}}".'],
            'sq.diajukan'   => [self::SQ_ROLES, 'SQ {{no_sq}} ("{{judul_order}}") diajukan oleh {{nama_user}} dan menunggu approval.'],
            'sq.disetujui'  => [self::SQ_ROLES, 'SQ {{no_sq}} ("{{judul_order}}") disetujui oleh {{nama_user}}.'],
            'sq.ditolak'    => [self::SQ_ROLES, 'SQ {{no_sq}} ("{{judul_order}}") ditolak oleh {{nama_user}}. Alasan: {{catatan}}'],
            'sq.win'        => [self::SQ_ROLES, 'SQ {{no_sq}} ("{{judul_order}}") ditandai Win oleh {{nama_user}}.'],
            'sq.lose'       => [self::SQ_ROLES, 'SQ {{no_sq}} ("{{judul_order}}") ditandai Lose oleh {{nama_user}}.'],
            'sq.terbit_so'  => [self::SQ_ROLES, 'SQ {{no_sq}} diterbitkan menjadi SO {{no_so}} oleh {{nama_user}}.'],

            'so.created'        => [$so, 'SO baru {{no_so}} berhasil dibuat oleh {{nama_user}}, dengan judul order "{{judul_order}}".'],
            'termin.siap_kirim' => [['pic_order', 'marketing_internal'], 'Termin {{no_termin}} pada SO {{no_so}} siap dikirim ({{nama_user}}).'],

            'wo.created'   => [$wo, 'WO baru {{no_wo}} berhasil dibuat oleh {{nama_user}}, dengan judul pekerjaan "{{judul_pekerjaan}}" (SO {{no_so}}).'],
            'wo.completed' => [$wo, 'WO {{no_wo}} ("{{judul_pekerjaan}}") telah diselesaikan oleh {{nama_user}}.'],

            'fwo.created'   => [$fwo, 'FWO baru {{no_fwo}} dibuat oleh {{nama_user}} untuk WO {{no_wo}}: "{{judul_pekerjaan}}".'],
            'fwo.completed' => [$fwo, 'FWO {{no_fwo}} ("{{judul_pekerjaan}}") telah diselesaikan oleh {{nama_user}}.'],

            'output_pekerjaan.siap' => [$out, 'Output "{{nama_output}}" pada WO {{no_wo}} sudah siap (oleh {{nama_user}}).'],
            'output_other.siap'     => [$out, 'Output Other "{{nama_item}}" ({{no_dokumen}}) sudah siap (oleh {{nama_user}}).'],

            'fwo_budget.verifikasi_dibutuhkan' => [['fwo_penyelia', 'wo_pic'], '{{nama_user}} menginput realisasi Rp {{nominal}} pada plan "{{label_plan}}" ({{no_fwo}}). Menunggu verifikasi.'],
            'fwo_budget.plan_selesai'          => [['fwo_penyelia', 'wo_pic'], 'Plan budget "{{label_plan}}" pada {{no_fwo}} telah diselesaikan oleh {{nama_user}}.'],
            'wo_budget.verifikasi_dibutuhkan'  => [['wo_pic'], '{{nama_user}} menginput realisasi Rp {{nominal}} pada plan "{{label_plan}}" ({{no_wo}}). Menunggu verifikasi.'],
            'wo_budget.plan_selesai'           => [['wo_pic'], 'Plan budget "{{label_plan}}" pada {{no_wo}} telah diselesaikan oleh {{nama_user}}.'],
        ];
    }

    public function up(): void
    {
        foreach ($this->plan() as $key => [$roles, $template]) {
            $def = NotificationCatalog::get($key);
            if (!$def) continue;

            $payload = [
                'module'           => $def['module'],
                'available_fields' => json_encode($def['fields']),
                'available_roles'  => json_encode(array_keys(NotificationCatalog::rolesOf($key))),
                'is_enabled'       => 1,
                'updated_at'       => now(),
            ];

            $event = DB::table('notification_events')->where('key', $key)->first();
            if ($event) {
                DB::table('notification_events')->where('id', $event->id)->update($payload);
                $eventId = $event->id;
            } else {
                $eventId = DB::table('notification_events')->insertGetId($payload + [
                    'key'         => $key,
                    'label'       => $def['label'],
                    'description' => $def['description'] ?? null,
                    'is_active'   => 1,
                    'created_at'  => now(),
                ]);
            }

            $sudahAda = DB::table('notification_rules')
                ->where('notification_event_id', $eventId)
                ->where('recipient_type', 'roles')
                ->exists();
            if ($sudahAda) continue;

            DB::table('notification_rules')->insert([
                'notification_event_id' => $eventId,
                'recipient_type'        => 'roles',
                'permission_slug'       => null,
                'recipient_roles'       => json_encode($roles),
                'message_template'      => $template,
                'is_active'             => 1,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Hapus hanya rule peran yang template-nya masih persis hasil seed ini; event tidak dihapus.
        foreach ($this->plan() as $key => [$roles, $template]) {
            $eventId = DB::table('notification_events')->where('key', $key)->value('id');
            if (!$eventId) continue;
            DB::table('notification_rules')
                ->where('notification_event_id', $eventId)
                ->where('recipient_type', 'roles')
                ->where('message_template', $template)
                ->delete();
        }
    }
};
