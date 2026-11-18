<?php

use App\Support\NotificationCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed event `sq.budget_selesai` (role "Peran dari data" = pic_input + marketing_internal, sama
 * pola dengan event sq.* lain yang sudah di-seed di 2026_10_28_000002). Idempoten.
 */
return new class extends Migration
{
    private const KEY = 'sq.budget_selesai';
    private const ROLES = ['pic_input', 'marketing_internal'];
    private const TEMPLATE = 'Budget SQ {{no_sq}} ("{{judul_order}}") ditandai selesai oleh {{nama_user}}.';

    public function up(): void
    {
        $def = NotificationCatalog::get(self::KEY);
        if (!$def) return;

        $payload = [
            'module'           => $def['module'],
            'available_fields' => json_encode($def['fields']),
            'available_roles'  => json_encode(array_keys(NotificationCatalog::rolesOf(self::KEY))),
            'is_enabled'       => 1,
            'updated_at'       => now(),
        ];

        $event = DB::table('notification_events')->where('key', self::KEY)->first();
        if ($event) {
            DB::table('notification_events')->where('id', $event->id)->update($payload);
            $eventId = $event->id;
        } else {
            $eventId = DB::table('notification_events')->insertGetId($payload + [
                'key'         => self::KEY,
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
        if ($sudahAda) return;

        DB::table('notification_rules')->insert([
            'notification_event_id' => $eventId,
            'recipient_type'        => 'roles',
            'permission_slug'       => null,
            'recipient_roles'       => json_encode(self::ROLES),
            'message_template'      => self::TEMPLATE,
            'is_active'             => 1,
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);
    }

    public function down(): void
    {
        $eventId = DB::table('notification_events')->where('key', self::KEY)->value('id');
        if (!$eventId) return;
        DB::table('notification_rules')
            ->where('notification_event_id', $eventId)
            ->where('recipient_type', 'roles')
            ->where('message_template', self::TEMPLATE)
            ->delete();
    }
};
