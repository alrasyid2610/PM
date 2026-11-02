<?php

namespace App\Http\Controllers;

use App\Support\DataScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use App\Support\StatusKepegawaian;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function data()
    {
        // Kolom login terakhir baru ada setelah migration 2026_10_28_000003 — dijaga supaya daftar tetap jalan sebelum itu.
        $adaLastLogin = Schema::hasColumn('users', 'last_login_at');

        $kolom = ['u.id', 'u.name', 'u.email', 'u.is_active', 'mg.name as group_name', 'u.created_at'];
        if ($adaLastLogin) $kolom[] = 'u.last_login_at';

        $query = DB::table('users as u')
            ->leftJoin('menu_groups as mg', 'mg.id', '=', 'u.menu_group_id')
            ->select($kolom);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('last_login_at', fn ($row) => !empty($row->last_login_at)
                ? \Illuminate\Support\Carbon::parse($row->last_login_at)->format('Y-m-d H:i')
                : 'Belum pernah')
            ->addColumn('status_label', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Tidak Aktif</span>';
            })
            ->rawColumns(['status_label'])
            ->make(true);
    }

    /** Nama Grup / Departemen (`menu_groups.name`) user yang boleh dipilih sebagai PIC Order di SO & SQ. */
    private const GRUP_PIC_ORDER = 'PIC';

    /**
     * Daftar user untuk Select2. `?peran=pic_order` (PIC Order SO/SQ) membatasi hasil ke user yang Grup /
     * Departemen-nya "PIC" — user tanpa grup atau berbeda grup tidak muncul. Tanpa parameter: semua user aktif.
     */
    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('users')
            ->where('is_active', 1)
            ->where('name', 'like', "%{$search}%")
            ->when($request->input('peran') === 'pic_order', fn($q) => $q->whereIn(
                'menu_group_id',
                DB::table('menu_groups')->whereRaw('LOWER(name) = ?', [mb_strtolower(self::GRUP_PIC_ORDER)])->select('id')
            ))
            ->select('id', 'name')
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($u) => ['id' => $u->id, 'text' => $u->name])
        );
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
            'no_hp'         => 'nullable|string|max:30',
            'is_active'     => 'required|in:0,1',
            'menu_group_id' => 'nullable|integer|exists:menu_groups,id',
            'id_atasan'     => 'nullable|integer|exists:users,id',
            'status_kepegawaian' => StatusKepegawaian::rule(),
        ]);

        try {
            DB::beginTransaction();

            $id = DB::table('users')->insertGetId([
                'name'          => $request->name,
                'email'         => $request->email,
                'password'      => Hash::make($request->password),
                'no_hp'         => $request->filled('no_hp') ? trim($request->no_hp) : null,
                'is_active'     => $request->is_active,
                'status_kepegawaian' => $request->filled('status_kepegawaian') ? $request->status_kepegawaian : null,
                'menu_group_id' => $request->filled('menu_group_id') ? $request->menu_group_id : null,
                'id_atasan'     => $request->filled('id_atasan') ? $request->id_atasan : null,
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
        $kolom = ['id', 'name', 'email', 'no_hp', 'is_active', 'menu_group_id', 'id_atasan', 'status_kepegawaian'];
        if (Schema::hasColumn('users', 'last_login_at')) array_push($kolom, 'last_login_at', 'last_login_ip');

        $user = DB::table('users')
            ->select($kolom)
            ->where('id', $id)
            ->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        $user->nama_atasan = $user->id_atasan ? DB::table('users')->where('id', $user->id_atasan)->value('name') : null;

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
            'no_hp'         => 'nullable|string|max:30',
            'is_active'     => 'required|in:0,1',
            'menu_group_id' => 'nullable|integer|exists:menu_groups,id',
            'id_atasan'     => 'nullable|integer|exists:users,id',
            'permissions'   => 'nullable|string',
            'status_kepegawaian' => StatusKepegawaian::rule(),
        ]);

        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();

            $before = (array) DB::table('users')->where('id', $id)->first();

            // Atasan: bukan diri sendiri & bukan bawahan sendiri (cegah siklus hierarki)
            if ($request->filled('id_atasan')) {
                $atasan = (int) $request->id_atasan;
                if ($atasan === (int) $id || in_array($atasan, DataScope::userIds((int) $id), true)) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Atasan tidak boleh diri sendiri atau bawahan sendiri'], 422);
                }
            }

            $updateData = [
                'name'          => $request->name,
                'email'         => $request->email,
                'no_hp'         => $request->filled('no_hp') ? trim($request->no_hp) : null,
                'is_active'     => $request->is_active,
                'status_kepegawaian' => $request->filled('status_kepegawaian') ? $request->status_kepegawaian : null,
                'menu_group_id' => $request->filled('menu_group_id') ? $request->menu_group_id : null,
                'id_atasan'     => $request->filled('id_atasan') ? $request->id_atasan : null,
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
