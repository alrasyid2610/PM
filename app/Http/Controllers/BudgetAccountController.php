<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetAccountController extends Controller
{
    /** Kolom `urutan` ada? (migration 2026_11_07_000001) — dijaga supaya halaman tidak error sebelum migrate dijalankan. */
    private static function adaUrutan(): bool
    {
        static $ada = null;
        return $ada ??= \Illuminate\Support\Facades\Schema::hasColumn('budget_accounts', 'urutan');
    }

    /** Urutkan kategori (parent): urutan master dulu, lalu nama. */
    private static function urutKategori($query)
    {
        if (self::adaUrutan()) $query->orderBy('urutan');
        return $query->orderBy('nama');
    }

    /**
     * Struktur untuk modal Budget Plan per kategori: semua kategori aktif (berurut `urutan`, lalu nama)
     * beserta sub kategorinya. Sub kategori nonaktif ikut dikirim (is_aktif = 0) supaya item lama
     * tetap bisa dipetakan ke kategorinya; yang nonaktif tidak ditawarkan untuk dipilih.
     * Lewat route select2 (segment terbuka) agar tidak butuh izin menu Budget Account.
     */
    private function sections(): \Illuminate\Http\JsonResponse
    {
        $parents = self::urutKategori(DB::table('budget_accounts')
            ->whereNull('deleted_at')->whereNull('id_parent')->where('is_aktif', 1))
            ->get(['id_account', 'nama']);

        $children = DB::table('budget_accounts')
            ->whereNull('deleted_at')->whereNotNull('id_parent')
            ->orderBy('nama')
            ->get(['id_account as id', 'id_parent', 'nama as text', 'kode', 'nilai_default', 'id_satuan_default', DB::raw('(SELECT s.nama FROM satuan s WHERE s.id_satuan = budget_accounts.id_satuan_default) as satuan_default'), 'is_aktif'])
            ->groupBy('id_parent');

        return response()->json($parents->map(fn($p) => [
            'id_kategori' => $p->id_account,
            'nama'        => $p->nama,
            'children'    => ($children[$p->id_account] ?? collect())->map(fn($c) => [
                'id'            => $c->id,
                'text'          => $c->text,
                'kode'          => $c->kode,
                'nilai_default' => $c->nilai_default,
                'id_satuan_default' => $c->id_satuan_default,
                'satuan_default' => $c->satuan_default,
                'is_aktif'      => (int) $c->is_aktif,
            ])->values(),
        ])->values());
    }

    public function select2(Request $request)
    {
        if ($request->input('mode') === 'sections') {
            return $this->sections();
        }

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
                ->get(['id_account as id', 'nama as text', 'kode', 'nilai_default', 'id_satuan_default', DB::raw('(SELECT s.nama FROM satuan s WHERE s.id_satuan = budget_accounts.id_satuan_default) as satuan_default')]);

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
                ->get(['id_account as id', 'nama as text', 'kode', 'nilai_default', 'id_satuan_default', DB::raw('(SELECT s.nama FROM satuan s WHERE s.id_satuan = budget_accounts.id_satuan_default) as satuan_default')]);

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
        $parents = self::urutKategori(DB::table('budget_accounts')
            ->whereNull('deleted_at')
            ->whereNull('id_parent'))
            ->select('budget_accounts.*', DB::raw('(SELECT s.nama FROM satuan s WHERE s.id_satuan = budget_accounts.id_satuan_default) as satuan_default'))
            ->get();

        $result = $parents->map(function ($parent) {
            $parent->children = DB::table('budget_accounts')
                ->whereNull('deleted_at')
                ->where('id_parent', $parent->id_account)
                ->orderBy('nama')
                ->select('budget_accounts.*', DB::raw('(SELECT s.nama FROM satuan s WHERE s.id_satuan = budget_accounts.id_satuan_default) as satuan_default'))
                ->get();
            return $parent;
        });

        return response()->json(['data' => $result]);
    }

    public function show(int $id)
    {
        $row = DB::table('budget_accounts')->where('id_account', $id)->select('budget_accounts.*', DB::raw('(SELECT s.nama FROM satuan s WHERE s.id_satuan = budget_accounts.id_satuan_default) as satuan_default'))->first();
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
            'nama'          => 'required|string|max:255',
            'kode'          => 'required|string|max:50',
            'id_parent'     => 'nullable|integer',
            'nilai_default' => 'nullable|integer|min:0',
            'id_satuan_default' => 'nullable|integer',
            'urutan'        => 'nullable|integer|min:0',
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

        $data = [
            'id_parent'     => $request->id_parent ?: null,
            'nama'          => $request->nama,
            'kode'          => strtoupper($request->kode),
            'nilai_default' => $request->filled('nilai_default') ? (int) $request->nilai_default : null,
            'id_satuan_default' => $request->filled('id_satuan_default') ? (int) $request->id_satuan_default : null,
            'keterangan'    => $request->keterangan,
            'is_aktif'      => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
        // Urutan hanya untuk kategori (parent); kosong → di akhir daftar
        if (self::adaUrutan() && !$request->id_parent) {
            $data['urutan'] = $request->filled('urutan')
                ? (int) $request->urutan
                : ((int) DB::table('budget_accounts')->whereNull('deleted_at')->whereNull('id_parent')->max('urutan')) + 1;
        }
        $id = DB::table('budget_accounts')->insertGetId($data);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'nama'          => 'required|string|max:255',
            'kode'          => 'required|string|max:50',
            'id_parent'     => 'nullable|integer',
            'nilai_default' => 'nullable|integer|min:0',
            'id_satuan_default' => 'nullable|integer',
            'urutan'        => 'nullable|integer|min:0',
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

        $data = [
            'id_parent'     => $request->id_parent ?: null,
            'nama'          => $request->nama,
            'kode'          => strtoupper($request->kode),
            'nilai_default' => $request->filled('nilai_default') ? (int) $request->nilai_default : null,
            'id_satuan_default' => $request->filled('id_satuan_default') ? (int) $request->id_satuan_default : null,
            'keterangan'    => $request->keterangan,
            'is_aktif'      => $request->boolean('is_aktif', true) ? 1 : 0,
            'updated_at'    => now(),
        ];
        // Urutan hanya untuk kategori (parent); diubah hanya bila dikirim
        if (self::adaUrutan() && !$request->id_parent && $request->filled('urutan')) {
            $data['urutan'] = (int) $request->urutan;
        }
        DB::table('budget_accounts')->where('id_account', $id)->update($data);

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
