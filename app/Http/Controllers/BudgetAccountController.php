<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetAccountController extends Controller
{
    public function select2(Request $request)
    {
        $q = $request->input('q', '');

        // Ambil semua parent (kategori)
        $parents = DB::table('budget_accounts')
            ->whereNull('deleted_at')
            ->whereNull('id_parent')
            ->where('is_aktif', 1)
            ->orderBy('nama')
            ->get(['id_account', 'nama']);

        $results = [];

        foreach ($parents as $parent) {
            $children = DB::table('budget_accounts')
                ->whereNull('deleted_at')
                ->where('id_parent', $parent->id_account)
                ->where('is_aktif', 1)
                ->when($q, fn($query) => $query->where(function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                          ->orWhere('kode', 'like', "%{$q}%");
                }))
                ->orderBy('nama')
                ->get(['id_account as id', 'nama as text', 'kode']);

            if ($children->isNotEmpty()) {
                $results[] = [
                    'text'     => $parent->nama,
                    'children' => $children->toArray(),
                ];
            }
        }

        // Jika ada pencarian, sertakan juga account tanpa parent yang cocok
        if ($q) {
            $orphans = DB::table('budget_accounts')
                ->whereNull('deleted_at')
                ->whereNull('id_parent')
                ->where('is_aktif', 1)
                ->where(function ($query) use ($q) {
                    $query->where('nama', 'like', "%{$q}%")
                          ->orWhere('kode', 'like', "%{$q}%");
                })
                ->orderBy('nama')
                ->get(['id_account as id', 'nama as text', 'kode']);

            if ($orphans->isNotEmpty()) {
                $results[] = [
                    'text'     => 'Lainnya',
                    'children' => $orphans->toArray(),
                ];
            }
        }

        return response()->json($results);
    }

    public function index()
    {
        $parents = DB::table('budget_accounts')
            ->whereNull('deleted_at')
            ->whereNull('id_parent')
            ->orderBy('nama')
            ->get();

        $result = $parents->map(function ($parent) {
            $parent->children = DB::table('budget_accounts')
                ->whereNull('deleted_at')
                ->where('id_parent', $parent->id_account)
                ->orderBy('nama')
                ->get();
            return $parent;
        });

        return response()->json(['data' => $result]);
    }

    public function show(int $id)
    {
        $row = DB::table('budget_accounts')->where('id_account', $id)->first();
        if (!$row) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        if ($row->id_parent) {
            $parent = DB::table('budget_accounts')->where('id_account', $row->id_parent)->first();
            $row->parent_nama = $parent?->nama;
        }

        return response()->json($row);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'      => 'required|string|max:255',
            'kode'      => 'required|string|max:50',
            'id_parent' => 'nullable|integer',
        ]);

        $exists = DB::table('budget_accounts')
            ->whereNull('deleted_at')
            ->where('kode', strtoupper($request->kode))
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kode sudah digunakan.',
                'errors'  => ['kode' => ['Kode sudah digunakan.']],
            ], 422);
        }

        // Validasi: parent tidak boleh punya id_parent sendiri (max 1 level)
        if ($request->id_parent) {
            $parentRow = DB::table('budget_accounts')->where('id_account', $request->id_parent)->first();
            if ($parentRow && $parentRow->id_parent) {
                return response()->json([
                    'message' => 'Tidak bisa membuat sub-kategori dari sub-kategori.',
                ], 422);
            }
        }

        $id = DB::table('budget_accounts')->insertGetId([
            'id_parent'  => $request->id_parent ?: null,
            'nama'       => $request->nama,
            'kode'       => strtoupper($request->kode),
            'keterangan' => $request->keterangan,
            'is_aktif'   => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nama'      => 'required|string|max:255',
            'kode'      => 'required|string|max:50',
            'id_parent' => 'nullable|integer',
        ]);

        // Validasi: parent tidak boleh punya id_parent sendiri
        if ($request->id_parent) {
            $parentRow = DB::table('budget_accounts')->where('id_account', $request->id_parent)->first();
            if ($parentRow && $parentRow->id_parent) {
                return response()->json([
                    'message' => 'Tidak bisa membuat sub-kategori dari sub-kategori.',
                ], 422);
            }
        }

        // Jika ini adalah parent, tidak boleh diubah jadi child selama masih punya child
        if ($request->id_parent) {
            $hasChildren = DB::table('budget_accounts')
                ->whereNull('deleted_at')
                ->where('id_parent', $id)
                ->exists();
            if ($hasChildren) {
                return response()->json([
                    'message' => 'Kategori ini masih memiliki item. Hapus item dulu sebelum mengubah kategori.',
                ], 422);
            }
        }

        DB::table('budget_accounts')->where('id_account', $id)->update([
            'id_parent'  => $request->id_parent ?: null,
            'nama'       => $request->nama,
            'kode'       => strtoupper($request->kode),
            'keterangan' => $request->keterangan,
            'is_aktif'   => $request->boolean('is_aktif', true) ? 1 : 0,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(int $id)
    {
        // Cek apakah masih punya child
        $hasChildren = DB::table('budget_accounts')
            ->whereNull('deleted_at')
            ->where('id_parent', $id)
            ->exists();

        if ($hasChildren) {
            return response()->json([
                'message' => 'Kategori ini masih memiliki item. Hapus item dulu sebelum menghapus kategori.',
            ], 422);
        }

        DB::table('budget_accounts')->where('id_account', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
