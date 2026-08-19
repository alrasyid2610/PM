<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class PersonnelController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string { return 'personnel'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_personnel']; }

    public function index()
    {
        return view('personnel.index', ['title' => 'Personnel']);
    }

    public function data()
    {
        $query = DB::table('personnel as p')
            ->whereNull('p.deleted_at')
            ->select(['p.id_personnel', 'p.nama', 'p.no_hp', 'p.is_aktif', 'p.id_user', 'p.created_at']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('akses_sistem', function ($row) {
                return $row->id_user
                    ? '<span class="badge rounded-pill" style="background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:600;">Punya Akses</span>'
                    : '<span class="text-muted" style="font-size:11px;">—</span>';
            })
            ->rawColumns(['akses_sistem'])
            ->make(true);
    }

    public function create()
    {
        return view('personnel.create', ['title' => 'Tambah Personnel']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'        => 'required|string|max:255',
            'no_hp'       => 'nullable|string|max:30',
            'keterangan'  => 'nullable|string',
            'is_aktif'    => 'nullable|boolean',
        ]);

        $id = DB::table('personnel')->insertGetId([
            'nama'       => $validated['nama'],
            'no_hp'      => $validated['no_hp'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'is_aktif'   => $request->boolean('is_aktif', true),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id_personnel');

        $after = DB::table('personnel')->where('id_personnel', $id)->get()->toJson();
        saveAudit('personnel', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'message' => 'Personnel berhasil dibuat', 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('personnel as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.id_user')
            ->where('p.id_personnel', $id)
            ->whereNull('p.deleted_at')
            ->select(['p.*', 'u.email as akun_email', 'u.is_active as akun_aktif'])
            ->first();

        if (!$data) return response()->json(['message' => 'Personnel tidak ditemukan'], 404);
        return response()->json($data);
    }

    public function detail($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama'       => 'required|string|max:255',
            'no_hp'      => 'nullable|string|max:30',
            'keterangan' => 'nullable|string',
            'is_aktif'   => 'nullable|boolean',
        ]);

        $before = DB::table('personnel')->where('id_personnel', $id)->get()->toJson();

        DB::table('personnel')->where('id_personnel', $id)->update([
            'nama'       => $validated['nama'],
            'no_hp'      => $validated['no_hp'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
            'is_aktif'   => $request->boolean('is_aktif', true),
            'updated_at' => now(),
        ]);

        $after = DB::table('personnel')->where('id_personnel', $id)->get()->toJson();
        saveAudit('personnel', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Personnel berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $inUse = DB::table('fieldwork_personels')->where('id_personnel', $id)->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Personnel tidak dapat dihapus karena masih ditugaskan di Fieldwork Order.',
            ], 422);
        }

        $before = DB::table('personnel')->where('id_personnel', $id)->get()->toJson();
        DB::table('personnel')->where('id_personnel', $id)->update(['deleted_at' => now()]);
        $after = DB::table('personnel')->where('id_personnel', $id)->get()->toJson();
        saveAudit('personnel', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('personnel')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->where('nama', 'like', "%{$search}%")
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->id_personnel, 'text' => $item->nama])
        );
    }

    // ── Akses Sistem — buat / cabut akun login untuk personnel ini ─────────────
    public function createAccount(Request $request, $id)
    {
        $personnel = DB::table('personnel')->where('id_personnel', $id)->whereNull('deleted_at')->first();
        if (!$personnel) return response()->json(['message' => 'Personnel tidak ditemukan'], 404);
        if ($personnel->id_user) {
            return response()->json(['success' => false, 'message' => 'Personnel ini sudah punya akun login.'], 422);
        }

        $validated = $request->validate([
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $userId = DB::table('users')->insertGetId([
            'name'       => $personnel->nama,
            'email'      => $validated['email'],
            'password'   => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('personnel')->where('id_personnel', $id)->update([
            'id_user'    => $userId,
            'updated_at' => now(),
        ]);

        saveAudit('personnel', $id, 'update', json_encode(['id_user' => null]), json_encode(['id_user' => $userId]));

        return response()->json(['success' => true, 'message' => 'Akun login berhasil dibuat untuk personnel ini']);
    }

    public function revokeAccount($id)
    {
        $personnel = DB::table('personnel')->where('id_personnel', $id)->whereNull('deleted_at')->first();
        if (!$personnel) return response()->json(['message' => 'Personnel tidak ditemukan'], 404);
        if (!$personnel->id_user) {
            return response()->json(['success' => false, 'message' => 'Personnel ini belum punya akun login.'], 422);
        }

        DB::table('users')->where('id', $personnel->id_user)->update(['is_active' => 0, 'updated_at' => now()]);
        DB::table('personnel')->where('id_personnel', $id)->update(['id_user' => null, 'updated_at' => now()]);

        saveAudit('personnel', $id, 'update', json_encode(['id_user' => $personnel->id_user]), json_encode(['id_user' => null]));

        return response()->json(['success' => true, 'message' => 'Akses sistem personnel ini berhasil dicabut']);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
