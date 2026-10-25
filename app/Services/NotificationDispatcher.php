<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\NotificationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Titik tunggal untuk memicu notifikasi dari kode (controller, dll).
 *
 * Pemakaian:
 *   NotificationDispatcher::fire('wo.created', [
 *       'title' => 'WO Baru: {{no_wo}}',
 *       'body'  => '{{judul_pekerjaan}}',
 *       'url'   => url('work-orders?open=' . $id),
 *   ], [
 *       'no_wo' => $no_wo, 'judul_pekerjaan' => ..., 'nama_user' => ...,
 *   ], NotificationRoles::wo($id));
 *
 * - Event harus ada di master (App\Support\NotificationCatalog) DAN sudah ditambahkan admin
 *   (notification_events.is_enabled = 1) DAN aktif. Selain itu fire() keluar tanpa efek.
 * - Placeholder di message_template memakai format {{key}}, diisi dari $data.
 * - $roles = peran penerima dari data record (lihat App\Support\NotificationRoles); peran
 *   'actor' (user yang sedang login) ditambahkan otomatis.
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
    public static function fire(string $eventKey, array $meta, array $data = [], array $roles = []): void
    {
        try {
            self::dispatch($eventKey, $meta, $data, $roles);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("NotificationDispatcher gagal untuk event [{$eventKey}]: " . $e->getMessage());
        }
    }

    private static function dispatch(string $eventKey, array $meta, array $data, array $roles): void
    {
        $event = NotificationEvent::where('key', $eventKey)->first();

        // Belum ditambahkan admin dari master / nonaktif → tidak kirim apa pun.
        if (!$event || !$event->is_enabled || !$event->is_active) {
            return;
        }

        $rules = NotificationRule::where('notification_event_id', $event->id)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            return;
        }

        if (Auth::id()) {
            $roles['actor'] = [(int) Auth::id()];
        }

        $defaultTitle = self::render($meta['title'] ?? $event->label, $data);
        $body         = isset($meta['body']) ? self::render($meta['body'], $data) : null;
        $url          = $meta['url'] ?? null;

        // 1 user = 1 baris notifikasi meski dia match lebih dari 1 rule (dedupe).
        // Kalau beberapa rule punya template berbeda, dipakai yang pertama match.
        $titleByUser = [];
        foreach ($rules as $rule) {
            $templateText = $rule->message_template ? self::render($rule->message_template, $data) : $defaultTitle;
            foreach (self::resolveRecipients($rule, $roles) as $userId) {
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

    private static function resolveRecipients(NotificationRule $rule, array $roles): array
    {
        return match ($rule->recipient_type) {
            'all'        => DB::table('users')->where('is_active', 1)->pluck('id')->all(),
            'users'      => $rule->recipients()->pluck('users.id')->all(),
            'permission' => self::resolveByPermission($rule->permission_slug),
            'roles'      => self::resolveByRoles($rule->recipient_roles ?? [], $roles),
            default      => [],
        };
    }

    /** Peran dari data record → user id aktif. @return int[] */
    private static function resolveByRoles(array|string|null $selected, array $roles): array
    {
        if (is_string($selected)) $selected = json_decode($selected, true) ?: [];
        $ids = [];
        foreach ((array) $selected as $role) {
            foreach ($roles[$role] ?? [] as $uid) {
                $ids[] = (int) $uid;
            }
        }
        if (empty($ids)) return [];

        return DB::table('users')->whereIn('id', array_unique($ids))->where('is_active', 1)->pluck('id')->all();
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
