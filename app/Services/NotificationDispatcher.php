<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\NotificationRule;
use Illuminate\Support\Facades\DB;

/**
 * Titik tunggal untuk memicu notifikasi dari kode (controller, dll).
 *
 * Pemakaian:
 *   NotificationDispatcher::fire('so.created', [
 *       'title' => "SO Baru: {{no_so}}",
 *       'body'  => "{{judul_order}} — {{nama_pelanggan}}",
 *       'url'   => url('sales-orders?open=' . $id),
 *       'label' => 'SO Dibuat', // dipakai kalau event baru pertama kali didaftarkan
 *   ], [
 *       'no_so' => $no_so, 'judul_order' => ..., 'nama_pelanggan' => ...,
 *   ]);
 *
 * - Kalau event key belum ada di katalog (`notification_events`), baris baru
 *   dibuat otomatis (self-register) dengan label dari $meta['label'] (fallback
 *   ke key itu sendiri), is_active = true — admin tinggal atur lanjutannya dari
 *   menu "Pengaturan Notifikasi", tidak perlu insert manual.
 * - Event yang di-nonaktifkan admin (is_active = false) tidak mengirim apa pun.
 * - Placeholder di message_template memakai format {{key}}, diisi dari $data.
 * - Penerima dari beberapa rule otomatis di-dedupe (1 user cuma 1 baris notifikasi
 *   per event meski match lebih dari 1 rule).
 */
class NotificationDispatcher
{
    /**
     * Dibungkus try-catch total dengan sengaja: fire() dipanggil di tengah alur
     * bisnis utama (mis. setelah SO berhasil disimpan). Kalau ada apa pun yang
     * salah di sistem notifikasi (migration belum jalan, rule tidak valid, dll),
     * itu TIDAK BOLEH menggagalkan aksi utama yang sedang berjalan — cukup
     * dicatat ke log, prosesnya tetap lanjut seperti notifikasi tidak ada.
     */
    public static function fire(string $eventKey, array $meta, array $data = []): void
    {
        try {
            self::dispatch($eventKey, $meta, $data);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("NotificationDispatcher gagal untuk event [{$eventKey}]: " . $e->getMessage());
        }
    }

    private static function dispatch(string $eventKey, array $meta, array $data): void
    {
        $event = NotificationEvent::firstOrCreate(
            ['key' => $eventKey],
            ['label' => $meta['label'] ?? $eventKey, 'is_active' => true]
        );

        self::rememberAvailableFields($event, array_keys($data));

        if (!$event->is_active) {
            return;
        }

        $rules = NotificationRule::where('notification_event_id', $event->id)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        $defaultTitle = self::render($meta['title'] ?? $event->label, $data);
        $body         = isset($meta['body']) ? self::render($meta['body'], $data) : null;
        $url          = $meta['url'] ?? null;

        // 1 user = 1 baris notifikasi meski dia match lebih dari 1 rule (dedupe).
        // Kalau beberapa rule punya template berbeda, dipakai yang pertama match.
        $titleByUser = [];
        foreach ($rules as $rule) {
            $templateText = $rule->message_template ? self::render($rule->message_template, $data) : $defaultTitle;
            foreach (self::resolveRecipients($rule) as $userId) {
                $titleByUser[$userId] ??= $templateText;
            }
        }

        if (empty($titleByUser)) {
            return;
        }

        $now  = now();
        $rows = [];
        foreach ($titleByUser as $userId => $title) {
            $rows[] = [
                'user_id'    => $userId,
                'event_key'  => $eventKey,
                'title'      => $title,
                'body'       => $body,
                'url'        => $url,
                'read_at'    => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Notification::insert($rows);
    }

    /** Gabungkan field baru ke available_fields event (union, bukan replace — field
     *  dari pemanggilan fire() sebelumnya tidak hilang walau kali ini datanya beda).
     *  Gagal di sini (mis. kolom belum ada karena migration belum jalan) tidak
     *  boleh menghentikan pengiriman notifikasi itu sendiri — cukup dilewati. */
    private static function rememberAvailableFields(NotificationEvent $event, array $keys): void
    {
        if (empty($keys)) return;

        try {
            $existing = $event->available_fields ?? [];
            $merged   = array_values(array_unique(array_merge($existing, $keys)));
            sort($merged);
            if ($merged !== $existing) {
                $event->update(['available_fields' => $merged]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Gagal mencatat available_fields event [{$event->key}]: " . $e->getMessage());
        }
    }

    private static function resolveRecipients(NotificationRule $rule): array
    {
        return match ($rule->recipient_type) {
            'all'        => DB::table('users')->where('is_active', 1)->pluck('id')->all(),
            'users'      => $rule->recipients()->pluck('users.id')->all(),
            'permission' => self::resolveByPermission($rule->permission_slug),
            default      => [],
        };
    }

    /** @return int[] */
    private static function resolveByPermission(?string $slug): array
    {
        if (!$slug) return [];

        $fromGroup = DB::table('users as u')
            ->join('menu_group_permissions as gp', 'gp.menu_group_id', '=', 'u.menu_group_id')
            ->where('gp.menu_slug', $slug)
            ->where('gp.can_read', 1)
            ->where('u.is_active', 1)
            ->pluck('u.id');

        $fromUser = DB::table('users as u')
            ->join('user_menu_permissions as up', 'up.user_id', '=', 'u.id')
            ->where('up.menu_slug', $slug)
            ->where('up.can_read', 1)
            ->where('u.is_active', 1)
            ->pluck('u.id');

        return $fromGroup->merge($fromUser)->unique()->values()->all();
    }

    /** Placeholder {{key}} → nilai dari $data. Key yang tidak ada dibiarkan apa adanya. */
    private static function render(string $template, array $data): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($data) {
            return array_key_exists($m[1], $data) ? (string) $data[$m[1]] : $m[0];
        }, $template);
    }
}
