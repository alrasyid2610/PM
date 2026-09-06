<?php

function getUserPermissions(int $userId): array
{
    return \Illuminate\Support\Facades\Cache::remember("user_perms_{$userId}", 300, function () use ($userId) {
        $DB = \Illuminate\Support\Facades\DB::class;

        // Group permissions
        $user = \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $userId)
            ->value('menu_group_id');

        $groupPerms = [];
        if ($user) {
            $rows = \Illuminate\Support\Facades\DB::table('menu_group_permissions')
                ->where('menu_group_id', $user)
                ->get();
            foreach ($rows as $row) {
                $groupPerms[$row->menu_slug] = [
                    'can_read'   => (bool) $row->can_read,
                    'can_create' => (bool) $row->can_create,
                    'can_update' => (bool) $row->can_update,
                    'can_delete' => (bool) $row->can_delete,
                ];
            }
        }

        // Individual override permissions
        $userPerms = [];
        $rows = \Illuminate\Support\Facades\DB::table('user_menu_permissions')
            ->where('user_id', $userId)
            ->get();
        foreach ($rows as $row) {
            $userPerms[$row->menu_slug] = [
                'can_read'   => (bool) $row->can_read,
                'can_create' => (bool) $row->can_create,
                'can_update' => (bool) $row->can_update,
                'can_delete' => (bool) $row->can_delete,
            ];
        }

        // Merge: OR logic — group OR individual override
        $allSlugs = array_unique(array_merge(array_keys($groupPerms), array_keys($userPerms)));
        $permissions = [];
        foreach ($allSlugs as $slug) {
            $g = $groupPerms[$slug] ?? ['can_read' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false];
            $u = $userPerms[$slug] ?? ['can_read' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false];
            $permissions[$slug] = [
                'can_read'   => $g['can_read']   || $u['can_read'],
                'can_create' => $g['can_create'] || $u['can_create'],
                'can_update' => $g['can_update'] || $u['can_update'],
                'can_delete' => $g['can_delete'] || $u['can_delete'],
            ];
        }

        return $permissions;
    });
}

function userCan(string $slug, string $action = 'can_read'): bool
{
    if (!\Illuminate\Support\Facades\Auth::check()) return false;
    $perms = getUserPermissions(\Illuminate\Support\Facades\Auth::id());
    return (bool) ($perms[$slug][$action] ?? false);
}

function uploadAttachment($fieldAttachments, $table)
{
    $files = [];

    if (!$fieldAttachments) {
        return [
            'status' => false,
            'message' => 'Tidak ada file',
            'files' => []
        ];
    }

    foreach ($fieldAttachments as $file) {

        $originalName = $file->getClientOriginalName();

        // bersihkan spasi
        $originalName = str_replace(' ', '_', $originalName);

        // prefix + timestamp supaya tidak overwrite
        $fileName = $table . '_' . time() . '_' . $originalName;

        $path = $file->storeAs(
            'attachments/' . $table,
            $fileName,
            'public'
        );

        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext === 'pdf'
            && \Illuminate\Support\Facades\Storage::disk('public')->size($path) > 2 * 1024 * 1024) {
            \App\Jobs\CompressPdfAttachment::dispatch($path);
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            compressImageIfLarge($path);
        }

        $files[] = $path;
    }

    return [
        'status' => true,
        'message' => 'Berhasil store file',
        'files' => $files
    ];
}

/**
 * Kompres gambar (JPEG/PNG) di storage disk 'public' pakai GD kalau
 * ukurannya melebihi threshold. Sinkron (bukan queue) karena prosesnya
 * cepat, beda dari kompresi PDF via Ghostscript. Menimpa file asli di
 * tempat hanya kalau hasil kompresi benar-benar lebih kecil. Gagal dengan
 * aman (skip, file asli dibiarkan apa adanya) kalau GD tidak mendukung
 * format tersebut atau terjadi error apapun.
 */
function compressImageIfLarge(string $path, int $thresholdBytes = 1024 * 1024): void
{
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    $fullPath = $disk->path($path);

    if (!file_exists($fullPath) || filesize($fullPath) <= $thresholdBytes) {
        return;
    }

    $originalSize = filesize($fullPath);
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

    try {
        $image = match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($fullPath),
            'png' => imagecreatefrompng($fullPath),
            default => null,
        };

        if (!$image) {
            return;
        }

        $tmpOutput = $fullPath . '.compressed.tmp';

        if ($ext === 'png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $tmpOutput, 8);
        } else {
            imagejpeg($image, $tmpOutput, 75);
        }

        imagedestroy($image);

        if (file_exists($tmpOutput) && filesize($tmpOutput) > 0 && filesize($tmpOutput) < $originalSize) {
            rename($tmpOutput, $fullPath);
        } elseif (file_exists($tmpOutput)) {
            unlink($tmpOutput);
        }
    } catch (\Throwable $e) {
        if (isset($tmpOutput) && file_exists($tmpOutput)) {
            @unlink($tmpOutput);
        }
        \Illuminate\Support\Facades\Log::warning('Kompresi gambar gagal, file asli dipakai apa adanya: ' . $e->getMessage());
    }
}
