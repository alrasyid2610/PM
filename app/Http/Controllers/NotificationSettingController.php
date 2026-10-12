<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Menu admin "Pengaturan Notifikasi" — kelola katalog event (notification_events)
 * dan aturan penerima + template pesan tiap event (notification_rules).
 * Halaman tunggal (bukan pola CrudPageController list+modal), karena datanya
 * sedikit (katalog event) dan bersarang (tiap event punya beberapa rule).
 */
class NotificationSettingController extends Controller
{
    public function index()
    {
        return view('notification-settings.index', ['menuSlugs' => collect($this->flattenMenuSlugs())]);
    }

    /**
     * Daftar slug menu (dari config/menus.php) untuk dropdown "Slug Menu" di form
     * Rule — supaya admin tidak perlu hafal/ketik manual. Grup "Aksi Khusus"
     * sengaja dikecualikan: slug di situ cuma punya can_update (can_read selalu
     * 0), jadi tidak relevan untuk recipient_type = 'permission' yang dicek dari
     * can_read.
     */
    private function flattenMenuSlugs(): array
    {
        $slugs = [];
        foreach (config('menus', []) as $group) {
            if (($group['group'] ?? null) === 'Aksi Khusus') continue;
            foreach ($group['items'] ?? [] as $item) {
                $slugs[] = [
                    'slug'  => $item['slug'],
                    'label' => $item['label'],
                    'group' => $group['group'] ?? null,
                ];
            }
        }
        return $slugs;
    }

    /** Katalog event + semua rule-nya, untuk render halaman. */
    public function data()
    {
        $events = DB::table('notification_events')->orderBy('label')->get();
        $rules  = DB::table('notification_rules')->orderBy('id')->get()->groupBy('notification_event_id');

        $events = $events->map(function ($e) use ($rules) {
            $e->rules = $rules->get($e->id, collect())->values();
            $e->available_fields = !empty($e->available_fields ?? null) ? json_decode($e->available_fields) : [];
            return $e;
        });

        return response()->json($events);
    }

    public function updateEvent(Request $request, $id)
    {
        $validated = $request->validate([
            'label'       => 'required|string|max:150',
            'description' => 'nullable|string',
            'is_active'   => 'required|boolean',
        ]);

        $event = DB::table('notification_events')->where('id', $id)->first();
        if (!$event) return response()->json(['message' => 'Event tidak ditemukan'], 404);

        DB::table('notification_events')->where('id', $id)->update([
            'label'       => $validated['label'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'],
            'updated_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Event diperbarui']);
    }

    public function storeRule(Request $request, $eventId)
    {
        $event = DB::table('notification_events')->where('id', $eventId)->first();
        if (!$event) return response()->json(['message' => 'Event tidak ditemukan'], 404);

        $validated = $this->validateRule($request);

        $id = DB::table('notification_rules')->insertGetId([
            'notification_event_id' => $eventId,
            'recipient_type'        => $validated['recipient_type'],
            'permission_slug'       => $validated['permission_slug'] ?? null,
            'message_template'      => $validated['message_template'],
            'is_active'             => $validated['is_active'],
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        if ($validated['recipient_type'] === 'users') {
            $this->syncRuleRecipients($id, $validated['user_ids'] ?? []);
        }

        return response()->json(['success' => true, 'message' => 'Rule ditambahkan', 'id' => $id]);
    }

    public function updateRule(Request $request, $id)
    {
        $rule = DB::table('notification_rules')->where('id', $id)->first();
        if (!$rule) return response()->json(['message' => 'Rule tidak ditemukan'], 404);

        $validated = $this->validateRule($request);

        DB::table('notification_rules')->where('id', $id)->update([
            'recipient_type'   => $validated['recipient_type'],
            'permission_slug'  => $validated['permission_slug'] ?? null,
            'message_template' => $validated['message_template'],
            'is_active'        => $validated['is_active'],
            'updated_at'       => now(),
        ]);

        if ($validated['recipient_type'] === 'users') {
            $this->syncRuleRecipients($id, $validated['user_ids'] ?? []);
        } else {
            DB::table('notification_rule_recipients')->where('notification_rule_id', $id)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Rule diperbarui']);
    }

    public function destroyRule($id)
    {
        $rule = DB::table('notification_rules')->where('id', $id)->first();
        if (!$rule) return response()->json(['message' => 'Rule tidak ditemukan'], 404);

        DB::table('notification_rule_recipients')->where('notification_rule_id', $id)->delete();
        DB::table('notification_rules')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Rule dihapus']);
    }

    private function validateRule(Request $request): array
    {
        return $request->validate([
            'recipient_type'            => 'required|in:all,permission,users',
            'permission_slug'           => 'nullable|string|max:100|required_if:recipient_type,permission',
            'message_template'          => 'required|string|max:500',
            'is_active'                 => 'required|boolean',
            'user_ids'                  => 'nullable|array',
            'user_ids.*'                => 'integer|exists:users,id',
        ], [
            'permission_slug.required_if' => 'Slug menu wajib diisi untuk tipe penerima "Akses Modul".',
        ]);
    }

    private function syncRuleRecipients(int $ruleId, array $userIds): void
    {
        DB::table('notification_rule_recipients')->where('notification_rule_id', $ruleId)->delete();
        if (empty($userIds)) return;

        $now  = now();
        $rows = array_map(fn ($uid) => [
            'notification_rule_id' => $ruleId,
            'user_id'              => $uid,
            'created_at'           => $now,
            'updated_at'           => $now,
        ], array_unique($userIds));

        DB::table('notification_rule_recipients')->insert($rows);
    }
}
