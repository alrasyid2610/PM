<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class FieldworkController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string        { return 'fieldworks'; }
    protected function auditExcludeFields(): array { return ['updated_at', 'created_at', 'id_fwo']; }

    public function index()
    {
        return view('fieldworks.index', ['title' => 'Fieldwork']);
    }

    public function create()
    {
        return view('fieldworks.create', ['title' => 'Tambah Fieldwork']);
    }

    public function data()
    {
        $query = DB::table('fieldworks as fw')
            ->select([
                'fw.id_fwo',
                'fw.no_fwo',
                'fw.judul_pekerjaan',
                'fw.tanggal_mulai',
                'fw.tanggal_selesai',
                'fw.waktu_kedatangan',
                'fw.created_at',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->make(true);
    }

    public function detail($id)
    {
        $data = DB::table('fieldworks as fw')
            ->leftJoin('work_orders as wo', 'fw.id_wo', '=', 'wo.id_wo')
            ->leftJoin('business_relation_sites as brs', 'fw.id_site_pelanggan_pekerjaan', '=', 'brs.id_site')
            ->leftJoin('business_relation_contacts as brc', 'fw.id_pic_pelanggan_pekerjaan', '=', 'brc.id_contact')
            ->where('fw.id_fwo', $id)
            ->select([
                'fw.*',
                'wo.no_wo as wo_no_wo',
                'wo.judul_pekerjaan as wo_judul_pekerjaan',
                'brs.nama_lokasi as site_name',
                'brc.nama_pic as pic_name',
            ])
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data->personels = DB::table('fieldwork_personels as fp')
            ->join('users as u', 'fp.id_user', '=', 'u.id')
            ->where('fp.id_fwo', $id)
            ->select(['fp.id_fwo_personel', 'fp.id_user', 'u.name as user_name', 'fp.role'])
            ->get();

        return response()->json($data);
    }

    public function updatePersonels(Request $request, $id)
    {
        $validated = $request->validate([
            'personels'           => 'nullable|array',
            'personels.*.id_user' => 'required|integer',
            'personels.*.role'    => 'nullable|string|max:500',
        ]);

        DB::table('fieldwork_personels')->where('id_fwo', $id)->delete();

        if (!empty($validated['personels'])) {
            DB::table('fieldwork_personels')->insert(
                array_map(fn($p) => [
                    'id_fwo'     => $id,
                    'id_user'    => $p['id_user'],
                    'role'       => $p['role'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $validated['personels'])
            );
        }

        return response()->json(['success' => true, 'message' => 'Personel berhasil diperbarui']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_wo'                       => 'required|integer',
            'judul_pekerjaan'             => 'required|string|max:500',
            'id_site_pelanggan_pekerjaan' => 'required|integer',
            'id_pic_pelanggan_pekerjaan'  => 'required|integer',
            'tanggal_mulai'               => 'nullable|date',
            'tanggal_selesai'             => 'nullable|date',
            'waktu_kedatangan'            => 'nullable|date',
            'keterangan'                  => 'nullable|string',
            'personels'                   => 'nullable|array',
            'personels.*.id_user'         => 'required|integer',
            'personels.*.role'            => 'nullable|string|max:500',
        ]);

        $id = DB::table('fieldworks')->insertGetId([
            'id_wo'                       => $validated['id_wo'],
            'judul_pekerjaan'             => $validated['judul_pekerjaan'],
            'id_site_pelanggan_pekerjaan' => $validated['id_site_pelanggan_pekerjaan'],
            'id_pic_pelanggan_pekerjaan'  => $validated['id_pic_pelanggan_pekerjaan'],
            'tanggal_mulai'               => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai'             => $validated['tanggal_selesai'] ?? null,
            'waktu_kedatangan'            => $validated['waktu_kedatangan'] ?? null,
            'keterangan'                  => $validated['keterangan'] ?? null,
            'no_fwo'                      => $this->generateNoFwo(),
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        if (!empty($validated['personels'])) {
            DB::table('fieldwork_personels')->insert(
                array_map(fn($p) => [
                    'id_fwo'     => $id,
                    'id_user'    => $p['id_user'],
                    'role'       => $p['role'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $validated['personels'])
            );
        }

        $after = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();
        saveAudit('fieldworks', $id, 'create', null, $after);

        return response()->json(['success' => true, 'message' => 'Fieldwork berhasil disimpan', 'id_fwo' => $id]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_wo'                       => 'required|integer',
            'judul_pekerjaan'             => 'required|string|max:500',
            'id_site_pelanggan_pekerjaan' => 'required|integer',
            'id_pic_pelanggan_pekerjaan'  => 'required|integer',
            'tanggal_mulai'               => 'nullable|date',
            'tanggal_selesai'             => 'nullable|date',
            'waktu_kedatangan'            => 'nullable|date',
            'keterangan'                  => 'nullable|string',
        ]);

        $before = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();

        DB::table('fieldworks')->where('id_fwo', $id)->update([
            ...$validated,
            'updated_at' => now(),
        ]);

        $after = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();
        saveAudit('fieldworks', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Fieldwork berhasil diperbarui']);
    }

    public function destroy($id)
    {
        DB::table('fieldworks')->where('id_fwo', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function duplicate(Request $request, int $id)
    {
        $source = DB::table('fieldworks')->where('id_fwo', $id)->first();
        if (!$source) {
            return response()->json(['message' => 'FWO tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'judul_pekerjaan'       => 'required|string|max:500',
            'tanggal_mulai'         => 'nullable|date',
            'tanggal_selesai'       => 'nullable|date',
            'keterangan'            => 'nullable|string',
            'personels'             => 'nullable|array',
            'personels.*.id_user'   => 'required|integer',
            'personels.*.role'      => 'nullable|string|max:500',
            'sections'              => 'nullable|array',
            'sections.*.id_boq'     => 'required|integer',
            'sections.*.qty'        => 'nullable|integer|min:1',
            'sections.*.keterangan' => 'nullable|string',
        ]);

        $no_fwo = $this->generateNoFwo();

        $newId = DB::table('fieldworks')->insertGetId([
            'id_wo'                       => $source->id_wo,
            'no_fwo'                      => $no_fwo,
            'judul_pekerjaan'             => $validated['judul_pekerjaan'],
            'id_site_pelanggan_pekerjaan' => $source->id_site_pelanggan_pekerjaan,
            'id_pic_pelanggan_pekerjaan'  => $source->id_pic_pelanggan_pekerjaan,
            'tanggal_mulai'               => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai'             => $validated['tanggal_selesai'] ?? null,
            'waktu_kedatangan'            => null,
            'keterangan'                  => $validated['keterangan'] ?? null,
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        if (!empty($validated['personels'])) {
            DB::table('fieldwork_personels')->insert(
                array_map(fn($p) => [
                    'id_fwo'     => $newId,
                    'id_user'    => $p['id_user'],
                    'role'       => $p['role'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $validated['personels'])
            );
        }

        if (!empty($validated['sections'])) {
            foreach ($validated['sections'] as $sec) {
                $boq = DB::table('boq')->where('id_boq', $sec['id_boq'])->first();
                if (!$boq) continue;

                $fwoBoqId = DB::table('fieldwork_boq')->insertGetId([
                    'id_fwo'           => $newId,
                    'id_boq'           => $sec['id_boq'],
                    'id_testing_point' => $boq->id_testing_point,
                    'qty'              => $sec['qty'] ?? null,
                    'keterangan'       => $sec['keterangan'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                $boqItems = DB::table('boq_items')->where('id_boq', $sec['id_boq'])->get();
                if ($boqItems->isNotEmpty()) {
                    DB::table('fieldwork_boq_items')->insert(
                        $boqItems->map(fn($item) => [
                            'id_fwo_boq'      => $fwoBoqId,
                            'id_testing_item' => $item->id_testing_item,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ])->toArray()
                    );
                }
            }
        }

        $after = DB::table('fieldworks')->where('id_fwo', $newId)->get()->toJson();
        saveAudit('fieldworks', $newId, 'create', null, $after);

        return response()->json([
            'success' => true,
            'message' => 'FWO berhasil disalin',
            'no_fwo'  => $no_fwo,
            'id_fwo'  => $newId,
        ]);
    }

    // ── Auto-numbering: FWO.YY.A.0001 → FWO.YY.A.9999 → FWO.YY.B.0001 ────────
    private function generateNoFwo(): string
    {
        $year = now()->format('y'); // "26"

        $latest = DB::table('fieldworks')
            ->where('no_fwo', 'like', "FWO.{$year}.%")
            ->orderBy('no_fwo', 'desc')
            ->value('no_fwo');

        if (!$latest) {
            return "FWO.{$year}.A.0001";
        }

        $parts  = explode('.', $latest); // ['FWO', '26', 'A', '0001']
        $letter = $parts[2] ?? 'A';
        $number = intval($parts[3] ?? 0);

        if ($number < 9999) {
            return sprintf('FWO.%s.%s.%04d', $year, $letter, $number + 1);
        }

        return sprintf('FWO.%s.%s.0001', $year, chr(ord($letter) + 1));
    }
}
