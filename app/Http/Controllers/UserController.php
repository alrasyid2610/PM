<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function data()
    {
        $query = DB::table('users as u')
            ->leftJoin('menu_groups as mg', 'mg.id', '=', 'u.menu_group_id')
            ->select(['u.id', 'u.name', 'u.email', 'u.is_active', 'mg.name as group_name', 'u.created_at']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_label', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Tidak Aktif</span>';
            })
            ->rawColumns(['status_label'])
            ->make(true);
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:8|confirmed',
            'is_active'     => 'required|in:0,1',
            'menu_group_id' => 'nullable|integer|exists:menu_groups,id',
        ]);

        try {
            DB::beginTransaction();

            $id = DB::table('users')->insertGetId([
                'name'          => $request->name,
                'email'         => $request->email,
                'password'      => Hash::make($request->password),
                'is_active'     => $request->is_active,
                'menu_group_id' => $request->filled('menu_group_id') ? $request->menu_group_id : null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            saveAudit('users', $id, 'create', null, json_encode(['name' => $request->name, 'email' => $request->email]));

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'message' => 'User berhasil dibuat']);
    }

    public function show($id)
    {
        $user = DB::table('users')
            ->select(['id', 'name', 'email', 'is_active', 'menu_group_id'])
            ->where('id', $id)
            ->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        $permRows = DB::table('user_menu_permissions')
            ->where('user_id', $id)
            ->get();

        $permissions = [];
        foreach ($permRows as $row) {
            $permissions[$row->menu_slug] = [
                'can_read'   => (bool) $row->can_read,
                'can_create' => (bool) $row->can_create,
                'can_update' => (bool) $row->can_update,
                'can_delete' => (bool) $row->can_delete,
            ];
        }

        return response()->json([...(array) $user, 'permissions' => $permissions]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => "required|email|unique:users,email,{$id}",
            'password'      => 'nullable|string|min:8',
            'is_active'     => 'required|in:0,1',
            'menu_group_id' => 'nullable|integer|exists:menu_groups,id',
            'permissions'   => 'nullable|string',
        ]);

        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();

            $before = (array) DB::table('users')->where('id', $id)->first();

            $updateData = [
                'name'          => $request->name,
                'email'         => $request->email,
                'is_active'     => $request->is_active,
                'menu_group_id' => $request->filled('menu_group_id') ? $request->menu_group_id : null,
                'updated_at'    => now(),
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            DB::table('users')->where('id', $id)->update($updateData);

            // Simpan permissions
            if ($request->filled('permissions')) {
                $permissions = json_decode($request->permissions, true);

                if (is_array($permissions)) {
                    foreach ($permissions as $slug => $actions) {
                        DB::table('user_menu_permissions')->updateOrInsert(
                            ['user_id' => $id, 'menu_slug' => $slug],
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
            }

            $after = (array) DB::table('users')->where('id', $id)->first();
            saveAudit('users', $id, 'update', json_encode($before), json_encode($after));

            DB::commit();

            Cache::forget("user_perms_{$id}");
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'message' => 'User berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();

            DB::table('user_menu_permissions')->where('user_id', $id)->delete();
            DB::table('users')->where('id', $id)->delete();

            saveAudit('users', $id, 'delete', json_encode((array) $user), null);

            DB::commit();

            Cache::forget("user_perms_{$id}");
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan server'], 500);
        }

        return response()->json(['success' => true, 'message' => 'User berhasil dihapus']);
    }

    public function history($id)
    {
        $logs = DB::table('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->select(['a.*', 'u.name as created_by_name'])
            ->where('a.nama_table', 'users')
            ->where('a.row_id', $id)
            ->orderByDesc('a.created_at')
            ->get();

        return response()->json($logs->map(function ($log) {
            $old = $log->old_value ? json_decode($log->old_value, true) : [];
            $new = $log->new_value ? json_decode($log->new_value, true) : [];

            $skip = ['password', 'remember_token', 'updated_at'];
            $changes = [];

            foreach (array_keys($new) as $key) {
                if (in_array($key, $skip)) continue;
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
}
