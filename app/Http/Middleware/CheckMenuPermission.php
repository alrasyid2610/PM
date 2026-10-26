<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckMenuPermission
{
    // Route pertama yang tidak perlu autentikasi
    private const PUBLIC_SEGMENTS = ['login', 'logout', 'up', 'test-pdf', 'test-pdf-view', 'test-pdf-download', 'test-so-pdf', 'test-fwo-view', 'wilayah', 'forgot-password', 'reset-password'];

    // Route yang tidak perlu cek permission (cukup auth)
    // 'notifications' = kotak masuk notifikasi milik user sendiri — bukan modul/menu,
    // jadi cukup login, tidak ada permission per-slug (beda dari 'notification-settings'
    // yang memang menu admin dan tetap dicek permission biasa).
    private const OPEN_SEGMENTS = ['dashboard', 'api', 'calendar', 'ui-guideline', 'profile', 'notifications'];

    // Modul yang di-nonaktifkan sementara — diblokir 403 untuk SEMUA user
    // (termasuk yang sudah punya permission tersimpan di DB), berlaku sampai
    // dihapus dari sini. Juga dihapus dari config/menus.php supaya tidak
    // muncul di sidebar maupun matrix permission (Grup Menu/User Management).
    // Saat ini kosong (Import Data Lab yang sebelumnya di sini sudah dihapus 2026-10-26).
    private const DISABLED_SEGMENTS = [];

    // Endpoint supporting — tidak perlu cek permission spesifik
    private const OPEN_LAST_SEGMENTS = ['select2', 'select2byid', 'data', 'pdf'];

    // Sub-route supporting dari modul lain
    private const OPEN_PATH_KEYWORDS = ['by-point', 'by-so', 'wo-progress', 'boq-progress', 'by-fwo'];

    // Mapping route prefix → menu slug (jika berbeda)
    private const SLUG_MAP = [
        'business-relation-sites'    => 'business-relations',
        'business-relation-contacts' => 'business-relations',
        'brs-sampling-points'        => 'business-relations',
        'brs-mp'                     => 'business-relations',
        'br-products'                => 'business-relations',
        'testing-items'              => 'testing-points',
        'fwo-budgets'                => 'fieldworks',
        'fwo-budget-actuals'         => 'fieldworks',
        'lab-samples'                => 'fieldworks',
        'fieldwork-boq'              => 'fieldworks',
        'fwo-boq-other'              => 'fieldworks',
        'fwo-boq-sampling'           => 'fieldworks',
        'output-pekerjaan'           => 'work-orders',
        'boq'                        => 'work-orders',
        'wo-budgets'                 => 'work-orders',
        'wo-budget-actuals'          => 'work-orders',
        'wo-samples'                 => 'work-orders',
        'wo-boq-other'               => 'work-orders',
        'wo-boq-sampling'            => 'work-orders',
        'wo-output-other'            => 'work-orders',
        'output-judul'               => 'work-orders',
        'so-output-other'            => 'sales-orders',
        'sq-boq'                     => 'sq-work-orders',
        'sq-boq-tambahan'            => 'sq-work-orders',
        'sq-wo-budgets'              => 'sq-work-orders',
        'sq-fwo-boq'                 => 'sq-fieldworks',
        'sq-fwo-budgets'             => 'sq-fieldworks',
    ];

    // Endpoint "Aksi Khusus" (POST) — dicek lewat slug aksinya sendiri, BUKAN
    // can_create menu induknya. Tanpa ini, user yang hanya boleh memverifikasi/
    // menyelesaikan (mis. finance) tetap ditolak karena semua POST otomatis
    // dianggap "create" pada menu induk. Format: segment pertama => [segment
    // terakhir => slug aksi]. Menu induk cukup can_read; controller tetap
    // mengecek slug aksi yang sama (lihat Project Reference — Aksi Khusus).
    // Sengaja TIDAK termasuk output-pekerjaan/{id}/status: endpoint itu juga
    // dipakai untuk transisi status lain yang bukan aksi khusus.
    private const ACTION_ROUTES = [
        'work-orders'        => ['complete' => 'wo-complete'],
        'fieldworks'         => ['complete' => 'fwo-complete'],
        'fwo-budgets'        => ['close' => 'fwo-budget-close'],
        'wo-budgets'         => ['close' => 'wo-budget-close'],
        'fwo-budget-actuals' => ['verify' => 'fwo-budget-verify', 'bulk-verify' => 'fwo-budget-verify'],
        'wo-budget-actuals'  => ['verify' => 'wo-budget-verify', 'bulk-verify' => 'wo-budget-verify'],
        'termin'             => ['siap-kirim' => 'termin-siap-kirim', 'selesai' => 'termin-selesai'],
        'sales-quotations'   => ['setujui' => 'sq-approve', 'tolak' => 'sq-approve'],
    ];

    // Endpoint yang HANYA dipakai oleh satu tab detail WO → dijaga oleh slug "Akses Tab"-nya
    // (grup "Akses Tab" di config/menus.php; aturan di userCanTab()). Prefix yang juga dipakai
    // halaman lain (boq, output-pekerjaan, fieldworks, work-orders/{id}/boq-progress) sengaja
    // BELUM dijaga di sini — lihat catatan fase berikutnya di WO.md.
    private const TAB_ROUTES = [
        'wo-boq-other'      => 'wo-tab-boq-other',
        'wo-boq-sampling'   => 'wo-tab-boq-sampling',
        'wo-output-other'   => 'wo-tab-output-other',
        'wo-budgets'        => 'wo-tab-budget',
        'wo-budget-actuals' => 'wo-tab-budget',
        'wo-samples'        => 'wo-tab-lab-sample-reg',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $segments = $request->segments();
        $first    = $segments[0] ?? null;

        // Tidak ada segment → root redirect, lewati
        if (!$first) return $next($request);

        // Route publik (login/logout)
        if (in_array($first, self::PUBLIC_SEGMENTS)) return $next($request);

        // Wajib login untuk semua route lain
        if (!auth()->check()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Unauthenticated'], 401)
                : redirect()->route('login');
        }

        // Modul yang di-nonaktifkan sementara — blokir semua orang, tanpa
        // terkecuali (tidak peduli permission tersimpan apa di DB)
        if (in_array($first, self::DISABLED_SEGMENTS)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Modul ini sedang dinonaktifkan'], 403)
                : abort(403, 'Modul ini sedang dinonaktifkan');
        }

        // Route yang open untuk semua user yang sudah login
        if (in_array($first, self::OPEN_SEGMENTS)) return $next($request);

        // Endpoint supporting — last segment
        $last = end($segments);
        if (in_array($last, self::OPEN_LAST_SEGMENTS)) return $next($request);

        // Sub-route keyword (misal: testing-items/by-point/5)
        foreach (self::OPEN_PATH_KEYWORDS as $keyword) {
            if (in_array($keyword, $segments)) return $next($request);
        }

        // Mapping slug
        $slug   = self::SLUG_MAP[$first] ?? $first;
        $action = $this->resolveAction($request);

        $perms = getUserPermissions(auth()->id());

        // Akses Tab: endpoint milik tab yang tidak diizinkan → 403 (berlaku untuk semua method).
        // wo-budgets/{id}/summary dikecualikan: itu data kartu ringkasan "Nilai Budget" di atas tab,
        // yang sementara tetap tampil untuk semua yang bisa membuka WO (keputusan 2026-10-26).
        $tabSlug = self::TAB_ROUTES[$first] ?? null;
        if ($tabSlug && $last !== 'summary' && !userCanTab($tabSlug)) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke tab ini'], 403)
                : abort(403, 'Anda tidak memiliki akses ke tab ini');
        }

        // Aksi Khusus: cukup can_read menu induk + can_update slug aksinya
        $actionSlug = $request->isMethod('POST') ? (self::ACTION_ROUTES[$first][$last] ?? null) : null;
        if ($actionSlug) {
            $allowed = ($perms[$slug]['can_read'] ?? false) && ($perms[$actionSlug]['can_update'] ?? false);
            if (!$allowed) {
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses untuk aksi ini'], 403)
                    : abort(403, 'Anda tidak memiliki akses untuk aksi ini');
            }
            return $next($request);
        }

        // Cek permission
        $allowed = $perms[$slug][$action] ?? false;

        if (!$allowed) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Akses ditolak'], 403)
                : abort(403, 'Anda tidak memiliki akses ke halaman ini');
        }

        return $next($request);
    }

    private function resolveAction(Request $request): string
    {
        $method = $request->method();
        $path   = $request->path();
        $last   = basename($path);

        // POST ke delete-attachment → update (bukan create)
        if ($method === 'POST' && str_contains($path, 'delete-attachment')) {
            return 'can_update';
        }

        // POST ke edit-context → update
        if ($method === 'POST' && str_contains($path, 'edit-context')) {
            return 'can_update';
        }

        if ($method === 'GET') {
            if ($last === 'create') return 'can_create';
            if ($last === 'edit')   return 'can_update';
            return 'can_read';
        }

        return match ($method) {
            'POST'         => 'can_create',
            'PUT', 'PATCH' => 'can_update',
            'DELETE'       => 'can_delete',
            default        => 'can_read',
        };
    }
}
