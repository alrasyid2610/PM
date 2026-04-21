<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckMenuPermission
{
    // Route pertama yang tidak perlu autentikasi
    private const PUBLIC_SEGMENTS = ['login', 'logout', 'up', 'test-pdf', 'test-pdf-view', 'test-pdf-download', 'test-so-pdf'];

    // Route yang tidak perlu cek permission (cukup auth)
    private const OPEN_SEGMENTS = ['dashboard', 'api'];

    // Endpoint supporting — tidak perlu cek permission spesifik
    private const OPEN_LAST_SEGMENTS = ['select2', 'select2byid', 'data'];

    // Sub-route supporting dari modul lain
    private const OPEN_PATH_KEYWORDS = ['by-point'];

    // Mapping route prefix → menu slug (jika berbeda)
    private const SLUG_MAP = [
        'business-relation-sites' => 'business-relations',
        'testing-items'           => 'testing-points',
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

        // Cek permission
        $perms   = getUserPermissions(auth()->id());
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
