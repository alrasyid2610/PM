<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Paket Testing Point — subset Testing Item milik 1 Testing Point (mis. "parameter 20" dari 50 item).
 * Hanya menyimpan referensi item induk (testing_point_paket_items), jadi data item selalu ikut induk.
 * Dipakai sebagai jalan pintas di modal Tambah Item BOQ (lihat byPoint()). Lihat Modules/Testing Points.md.
 */
class TestingPointPaketController extends Controller
{
    /** Daftar paket 1 Testing Point untuk tab "Paket" (jumlah item = item induk yang masih aktif). */
    public function index($id)
    {
        $pakets = DB::table('testing_point_paket')->where('id_testing_point', $id)->orderBy('nama')->get();
        $items  = $this->itemIdsByPaket($pakets->pluck('id_paket'));
        $aktif  = DB::table('testing_items')->where('id_testing_point', $id)->where('is_aktif', 1)->pluck('id_testing_item')->flip();

        return response()->json($pakets->map(function ($p) use ($items, $aktif) {
            $ids = $items->get($p->id_paket, collect());
            return [
                'id_paket'    => $p->id_paket,
                'nama'        => $p->nama,
                'item_ids'    => $ids->values(),
                'jumlah_item' => $ids->filter(fn ($i) => $aktif->has($i))->count(),
            ];
        })->values());
    }

    /**
     * Paket untuk dropdown di modal Tambah Item BOQ — hanya item AKTIF (item nonaktif tidak ikut tercentang).
     * Endpoint terbuka seperti testing-items/by-point (keyword 'paket-by-point' di CheckMenuPermission).
     */
    public function byPoint($id)
    {
        $pakets = DB::table('testing_point_paket')->where('id_testing_point', $id)->orderBy('nama')->get();
        $items  = $this->itemIdsByPaket($pakets->pluck('id_paket'));
        $aktif  = DB::table('testing_items')->where('id_testing_point', $id)->where('is_aktif', 1)->pluck('id_testing_item')->flip();

        return response()->json(['data' => $pakets->map(function ($p) use ($items, $aktif) {
            $ids = $items->get($p->id_paket, collect())->filter(fn ($i) => $aktif->has($i))->values();
            return ['id_paket' => $p->id_paket, 'nama' => $p->nama, 'item_ids' => $ids, 'jumlah_item' => $ids->count()];
        })->filter(fn ($p) => $p['jumlah_item'] > 0)->values()]);
    }

    public function store(Request $request, $id)
    {
        if (!DB::table('testing_points')->where('id_testing_point', $id)->whereNull('deleted_at')->exists()) {
            return response()->json(['message' => 'Testing Point tidak ditemukan'], 404);
        }
        [$nama, $itemIds, $error] = $this->validated($request, $id, null);
        if ($error) return $error;

        $idPaket = DB::transaction(function () use ($id, $nama, $itemIds) {
            $idPaket = DB::table('testing_point_paket')->insertGetId([
                'id_testing_point' => $id, 'nama' => $nama, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('testing_point_paket_items')->insert(array_map(fn ($i) => ['id_paket' => $idPaket, 'id_testing_item' => $i], $itemIds));
            return $idPaket;
        });

        saveAudit('testing_point_paket', $idPaket, 'create', '', json_encode(['nama' => $nama, 'jumlah_item' => count($itemIds)]), ['testing_points', $id]);

        return response()->json(['success' => true, 'id_paket' => $idPaket]);
    }

    public function update(Request $request, $id, $paket)
    {
        $row = DB::table('testing_point_paket')->where('id_paket', $paket)->where('id_testing_point', $id)->first();
        if (!$row) return response()->json(['message' => 'Paket tidak ditemukan'], 404);

        [$nama, $itemIds, $error] = $this->validated($request, $id, $paket);
        if ($error) return $error;

        $before = json_encode(['nama' => $row->nama, 'jumlah_item' => DB::table('testing_point_paket_items')->where('id_paket', $paket)->count()]);

        DB::transaction(function () use ($paket, $nama, $itemIds) {
            DB::table('testing_point_paket')->where('id_paket', $paket)->update(['nama' => $nama, 'updated_at' => now()]);
            DB::table('testing_point_paket_items')->where('id_paket', $paket)->delete();
            DB::table('testing_point_paket_items')->insert(array_map(fn ($i) => ['id_paket' => $paket, 'id_testing_item' => $i], $itemIds));
        });

        saveAudit('testing_point_paket', $paket, 'update', $before, json_encode(['nama' => $nama, 'jumlah_item' => count($itemIds)]), ['testing_points', $id]);

        return response()->json(['success' => true]);
    }

    public function destroy($id, $paket)
    {
        $row = DB::table('testing_point_paket')->where('id_paket', $paket)->where('id_testing_point', $id)->first();
        if (!$row) return response()->json(['message' => 'Paket tidak ditemukan'], 404);

        $before = json_encode(['nama' => $row->nama, 'jumlah_item' => DB::table('testing_point_paket_items')->where('id_paket', $paket)->count()]);

        DB::transaction(function () use ($paket) {
            DB::table('testing_point_paket_items')->where('id_paket', $paket)->delete();
            DB::table('testing_point_paket')->where('id_paket', $paket)->delete();
        });

        saveAudit('testing_point_paket', $paket, 'delete', $before, '', ['testing_points', $id]);

        return response()->json(['success' => true, 'message' => 'Paket berhasil dihapus']);
    }

    /** Hapus semua paket 1 Testing Point (dipanggil saat Testing Point dihapus). */
    public static function hapusSemua($idTestingPoint): void
    {
        $ids = DB::table('testing_point_paket')->where('id_testing_point', $idTestingPoint)->pluck('id_paket');
        if ($ids->isEmpty()) return;
        DB::table('testing_point_paket_items')->whereIn('id_paket', $ids)->delete();
        DB::table('testing_point_paket')->whereIn('id_paket', $ids)->delete();
    }

    /** Lepas item yang dihapus dari induk dari semua paket (dipanggil saat Simpan Testing Point menghapus baris item). */
    public static function lepasItem(array $idTestingItems): void
    {
        if ($idTestingItems) DB::table('testing_point_paket_items')->whereIn('id_testing_item', $idTestingItems)->delete();
    }

    private function itemIdsByPaket($idPakets)
    {
        if ($idPakets->isEmpty()) return collect();
        return DB::table('testing_point_paket_items as pi')
            ->join('testing_items as ti', 'ti.id_testing_item', '=', 'pi.id_testing_item')
            ->whereIn('pi.id_paket', $idPakets)
            ->orderBy('ti.nomor')
            ->get(['pi.id_paket', 'pi.id_testing_item'])
            ->groupBy('id_paket')
            ->map(fn ($g) => $g->pluck('id_testing_item')->map(fn ($v) => (int) $v));
    }

    /** @return array{0:string,1:array,2:?\Illuminate\Http\JsonResponse} [nama, item_ids, error] */
    private function validated(Request $request, $idTestingPoint, $exceptPaket): array
    {
        $data = $request->validate([
            'nama'       => 'required|string|max:255',
            'item_ids'   => 'required|array|min:1',
            'item_ids.*' => 'integer',
        ], [
            'nama.required'     => 'Nama paket wajib diisi.',
            'item_ids.required' => 'Pilih minimal 1 Testing Item untuk paket.',
            'item_ids.min'      => 'Pilih minimal 1 Testing Item untuk paket.',
        ]);

        $nama = trim($data['nama']);
        $dobel = DB::table('testing_point_paket')
            ->where('id_testing_point', $idTestingPoint)
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
            ->when($exceptPaket, fn ($q) => $q->where('id_paket', '!=', $exceptPaket))
            ->exists();
        if ($dobel) {
            return [$nama, [], response()->json(['message' => 'Nama paket "' . $nama . '" sudah dipakai di Testing Point ini.'], 422)];
        }

        // Item harus milik Testing Point ini
        $itemIds = collect($data['item_ids'])->map(fn ($v) => (int) $v)->unique()->values();
        $valid = DB::table('testing_items')->where('id_testing_point', $idTestingPoint)->whereIn('id_testing_item', $itemIds)->pluck('id_testing_item')->map(fn ($v) => (int) $v);
        if ($valid->count() !== $itemIds->count()) {
            return [$nama, [], response()->json(['message' => 'Ada item yang bukan milik Testing Point ini.'], 422)];
        }

        return [$nama, $itemIds->all(), null];
    }
}
