<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class MenuGroupController extends Controller
{
    public function index()
    {
        return view('menu-groups.index');
    }

    public function data()
    {
        $query = DB::table('menu_groups')
            ->select(['id', 'name', 'description', 'created_at']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        return view('menu-groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:menu_groups,name',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $id = DB::table('menu_groups')->insertGetId([
                'name'        => $request->name,
                'description' => $request->description,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $this->syncPermissions($id, $request->permissions);

            saveAudit('menu_groups', $id, 'create', null, json_encode(['name' => $request->name]));

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Grup menu berhasil dibuat']);
    }

    public function show($id)
    {
        $group = DB::table('menu_groups')->where('id', $id)->first();

        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Grup tidak ditemukan'], 404);
        }

        $permRows = DB::table('menu_group_permissions')->where('menu_group_id', $id)->get();
        $permissions = [];
        foreach ($permRows as $row) {
            $permissions[$row->menu_slug] = [
                'can_read'   => (bool) $row->can_read,
                'can_create' => (bool) $row->can_create,
                'can_update' => (bool) $row->can_update,
                'can_delete' => (bool) $row->can_delete,
            ];
        }

        return response()->json([...(array) $group, 'permissions' => $permissions]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'        => "required|string|max:100|unique:menu_groups,name,{$id}",
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|string',
        ]);

        $group = DB::table('menu_groups')->where('id', $id)->first();
        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Grup tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();

            $before = (array) $group;

            DB::table('menu_groups')->where('id', $id)->update([
                'name'        => $request->name,
                'description' => $request->description,
                'updated_at'  => now(),
            ]);

            $this->syncPermissions($id, $request->permissions);

            $after = (array) DB::table('menu_groups')->where('id', $id)->first();
            saveAudit('menu_groups', $id, 'update', json_encode($before), json_encode($after));

            DB::commit();

            // Clear cache semua user yang ada di grup ini
            $this->clearGroupUsersCache($id);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Grup menu berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $group = DB::table('menu_groups')->where('id', $id)->first();
        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Grup tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();

            $this->clearGroupUsersCache($id);

            // SET NULL on users handled by FK constraint
            DB::table('menu_group_permissions')->where('menu_group_id', $id)->delete();
            DB::table('menu_groups')->where('id', $id)->delete();

            saveAudit('menu_groups', $id, 'delete', json_encode((array) $group), null);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Grup menu berhasil dihapus']);
    }

    public function history($id)
    {
        $logs = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->select(['a.*', 'u.name as created_by_name'])
            ->where('a.nama_table', 'menu_groups')
            ->where('a.row_id', $id)
            ->orderByDesc('a.created_at')
            ->get();

        return response()->json($logs->map(function ($log) {
            $old = $log->old_value ? json_decode($log->old_value, true) : [];
            $new = $log->new_value ? json_decode($log->new_value, true) : [];

            $changes = [];
            foreach (array_keys($new) as $key) {
                $oldVal = $old[$key] ?? null;
                $newVal = $new[$key] ?? null;
                if ($oldVal != $newVal) {
                    $changes[] = ['field' => $key, 'old_value' => $oldVal, 'new_value' => $newVal];
                }
            }

            return [
                'action'          => $log->action,
                'created_at'      => $log->created_at,
                'created_by_name' => $log->created_by_name ?? 'System',
                'total_changes'   => count($changes),
                'changes'         => $changes,
            ];
        }));
    }

    private function syncPermissions(int $groupId, ?string $permissionsJson): void
    {
        if (!$permissionsJson) return;

        $permissions = json_decode($permissionsJson, true);
        if (!is_array($permissions)) return;

        foreach ($permissions as $slug => $actions) {
            DB::table('menu_group_permissions')->updateOrInsert(
                ['menu_group_id' => $groupId, 'menu_slug' => $slug],
                [
                    'can_read'   => $actions['can_read']   ?? 0,
                    'can_create' => $actions['can_create'] ?? 0,
                    'can_update' => $actions['can_update'] ?? 0,
                    'can_delete' => $actions['can_delete'] ?? 0,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function clearGroupUsersCache(int $groupId): void
    {
        $userIds = DB::table('users')->where('menu_group_id', $groupId)->pluck('id');
        foreach ($userIds as $uid) {
            Cache::forget("user_perms_{$uid}");
        }
    }
}
