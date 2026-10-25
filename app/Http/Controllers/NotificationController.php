<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Kotak masuk notifikasi milik user yang sedang login. Fase 1: data dimuat
 * saat halaman dibuka/di-refresh (lewat index()) — belum ada polling otomatis.
 */
class NotificationController extends Controller
{
    /** Daftar notifikasi + jumlah belum dibaca, untuk dropdown lonceng. */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $limit  = (int) $request->input('limit', 20);

        $items = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $unread = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->count();

        return response()->json(['items' => $items, 'unread' => $unread]);
    }

    public function markRead($id)
    {
        $row = DB::table('notifications')->where('id', $id)->where('user_id', Auth::id())->first();
        if (!$row) return response()->json(['message' => 'Notifikasi tidak ditemukan'], 404);

        if (!$row->read_at) {
            DB::table('notifications')->where('id', $id)->update(['read_at' => now(), 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        DB::table('notifications')
            ->where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    /** Soft delete — hilang dari daftar user ini saja. */
    public function destroy($id)
    {
        $row = DB::table('notifications')->where('id', $id)->where('user_id', Auth::id())->first();
        if (!$row) return response()->json(['message' => 'Notifikasi tidak ditemukan'], 404);

        DB::table('notifications')->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }
}
