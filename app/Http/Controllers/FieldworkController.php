<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;
use Spatie\LaravelPdf\Facades\Pdf;

class FieldworkController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'fieldworks';
    }
    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_fwo'];
    }

    public function index()
    {
        return view('fieldworks.index', ['title' => 'Fieldwork']);
    }

    public function create()
    {
        // Default Penyelia = user "Saldi" (kalau ada). Bisa diganti di form.
        $defaultPenyelia = DB::table('users')->where('name', 'Saldi')->first(['id', 'name']);

        return view('fieldworks.create', [
            'title' => 'Tambah Fieldwork',
            'defaultPenyelia' => $defaultPenyelia,
        ]);
    }

    public function data(Request $request)
    {
        $filters     = $request->input('filters', []);
        $isSearching = !empty($filters);

        $query = DB::table('fieldworks as fw')
            ->leftJoin('work_orders as wo', 'fw.id_wo', '=', 'wo.id_wo')
            ->when(!$isSearching, fn($q) => $q->whereNull('fw.deleted_at'))
            ->when(!$isSearching, fn($q) => $q->where('fw.status', '!=', 'completed'))
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $by   = $f['by']  ?? '';
                    $term = trim($f['q'] ?? '');
                    if (!$by || $term === '') continue;
                    $like = '%' . $term . '%';
                    match ($by) {
                        'no_fwo' => $q->where('fw.no_fwo', 'like', $like),
                        'no_wo'  => $q->where('wo.no_wo',  'like', $like),
                        'judul'  => $q->where('fw.judul_pekerjaan', 'like', $like),
                        'status' => match ($term) {
                            'all'     => null,
                            'deleted' => $q->whereNotNull('fw.deleted_at'),
                            default   => $q->whereNull('fw.deleted_at')->where('fw.status', $term),
                        },
                        default  => null,
                    };
                }
            })
            ->select([
                'fw.id_fwo',
                DB::raw("CASE WHEN fw.deleted_at IS NOT NULL THEN 'deleted' ELSE fw.status END as status"),
                'fw.no_fwo',
                'wo.no_wo',
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
            ->leftJoin('sales_orders as so', 'wo.id_so', '=', 'so.id_so')
            ->leftJoin('business_relation_sites as brs', 'fw.id_site_pelanggan_pekerjaan', '=', 'brs.id_site')
            ->leftJoin('business_relation_contacts as brc', 'fw.id_pic_pelanggan_pekerjaan', '=', 'brc.id_contact')
            ->leftJoin('users as pny', 'pny.id', '=', 'fw.id_penyelia')
            ->where('fw.id_fwo', $id)
            ->leftJoin('business_relation_sites as brs_wo', 'wo.id_site_pelanggan_pekerjaan', '=', 'brs_wo.id_site')
            ->leftJoin('business_relations as br_wo', 'br_wo.id_br', '=', 'wo.id_pelanggan_pekerjaan')
            ->leftJoin('entitas as ent_wo', 'ent_wo.id_entitas', '=', 'br_wo.id_entitas')
            ->select([
                'fw.*',
                'wo.no_wo as wo_no_wo',
                'wo.id_so',
                'wo.id_pelanggan_pekerjaan',
                'br_wo.nama as nama_pelanggan_pekerjaan',
                'ent_wo.nama as entitas_pelanggan_pekerjaan',
                'wo.judul_pekerjaan as wo_judul_pekerjaan',
                'wo.status as wo_status',
                'so.no_so',
                'brs.nama_lokasi as site_name',
                'brc.nama_pic as pic_name',
                'brs_wo.nama_lokasi as wo_site_name',
                'pny.name as nama_penyelia',
            ])
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data->nama_pelanggan_display = brDisplayName($data->entitas_pelanggan_pekerjaan, $data->nama_pelanggan_pekerjaan);

        $data->personels = DB::table('fieldwork_personels as fp')
            ->join('personnel as p', 'fp.id_personnel', '=', 'p.id_personnel')
            ->where('fp.id_fwo', $id)
            ->select(['fp.id_fwo_personel', 'fp.id_personnel', 'p.nama as user_name', 'fp.role'])
            ->get();

        return response()->json($data);
    }

    public function updatePersonels(Request $request, $id)
    {
        $validated = $request->validate([
            'personels'                 => 'nullable|array',
            'personels.*.id_personnel'  => 'required|integer',
            'personels.*.role'          => 'nullable|string|max:500',
        ]);

        DB::table('fieldwork_personels')->where('id_fwo', $id)->delete();

        if (!empty($validated['personels'])) {
            DB::table('fieldwork_personels')->insert(
                array_map(fn($p) => [
                    'id_fwo'       => $id,
                    'id_personnel' => $p['id_personnel'],
                    'role'         => $p['role'] ?? null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
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
            'id_penyelia'                 => 'nullable|integer|exists:users,id',
            'tanggal_mulai'               => 'nullable|date',
            'tanggal_selesai'             => 'nullable|date',
            'waktu_kedatangan'            => 'nullable|date',
            'status'                      => 'nullable|string|in:planned,completed',
            'keterangan'                  => 'nullable|string',
            'personels'                   => 'nullable|array',
            'personels.*.id_personnel'    => 'required|integer',
            'personels.*.role'            => 'nullable|string|max:500',
        ]);

        $wo = DB::table('work_orders')->where('id_wo', $validated['id_wo'])->first();
        if ($wo) {
            $woMulai   = $wo->tanggal_mulai   ? substr($wo->tanggal_mulai, 0, 10)   : null;
            $woSelesai = $wo->tanggal_selesai ? substr($wo->tanggal_selesai, 0, 10) : null;
            $fwoMulai   = !empty($validated['tanggal_mulai'])   ? substr($validated['tanggal_mulai'], 0, 10)   : null;
            $fwoSelesai = !empty($validated['tanggal_selesai']) ? substr($validated['tanggal_selesai'], 0, 10) : null;

            if ($fwoMulai && $woMulai && $fwoMulai < $woMulai) {
                return response()->json(['message' => 'Tanggal mulai FWO tidak boleh sebelum tanggal mulai WO (' . $woMulai . ')'], 422);
            }
            if ($fwoMulai && $woSelesai && $fwoMulai > $woSelesai) {
                return response()->json(['message' => 'Tanggal mulai FWO tidak boleh setelah tanggal selesai WO (' . $woSelesai . ')'], 422);
            }
            if ($fwoSelesai && $woMulai && $fwoSelesai < $woMulai) {
                return response()->json(['message' => 'Tanggal selesai FWO tidak boleh sebelum tanggal mulai WO (' . $woMulai . ')'], 422);
            }
            if ($fwoSelesai && $woSelesai && $fwoSelesai > $woSelesai) {
                return response()->json(['message' => 'Tanggal selesai FWO tidak boleh setelah tanggal selesai WO (' . $woSelesai . ')'], 422);
            }
        }

        $fwoMulai         = !empty($validated['tanggal_mulai'])   ? substr($validated['tanggal_mulai'], 0, 10) : null;
        $fwoSelesaiCheck  = !empty($validated['tanggal_selesai']) ? substr($validated['tanggal_selesai'], 0, 10) : null;
        $waktuKedatangan  = !empty($validated['waktu_kedatangan']) ? substr($validated['waktu_kedatangan'], 0, 10) : null;
        if ($fwoMulai && $waktuKedatangan && $waktuKedatangan < $fwoMulai) {
            return response()->json(['message' => 'Waktu kedatangan tidak boleh lebih kecil dari tanggal mulai FWO (' . $fwoMulai . ')'], 422);
        }
        if ($fwoSelesaiCheck && $waktuKedatangan && $waktuKedatangan > $fwoSelesaiCheck) {
            return response()->json(['message' => 'Waktu kedatangan tidak boleh lebih besar dari tanggal selesai FWO (' . $fwoSelesaiCheck . ')'], 422);
        }

        $id = DB::table('fieldworks')->insertGetId([
            'id_wo'                       => $validated['id_wo'],
            'judul_pekerjaan'             => $validated['judul_pekerjaan'],
            'id_site_pelanggan_pekerjaan' => $validated['id_site_pelanggan_pekerjaan'],
            'id_pic_pelanggan_pekerjaan'  => $validated['id_pic_pelanggan_pekerjaan'],
            'id_penyelia'                 => $validated['id_penyelia'] ?? null,
            'tanggal_mulai'               => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai'             => $validated['tanggal_selesai'] ?? null,
            'waktu_kedatangan'            => $validated['waktu_kedatangan'] ?? null,
            'status'                      => $validated['status'] ?? 'planned',
            'keterangan'                  => $validated['keterangan'] ?? null,
            'no_fwo'                      => $this->generateNoFwo(),
            'created_at'                  => now(),
            'updated_at'                  => now(),
        ]);

        if (!empty($validated['personels'])) {
            DB::table('fieldwork_personels')->insert(
                array_map(fn($p) => [
                    'id_fwo'       => $id,
                    'id_personnel' => $p['id_personnel'],
                    'role'         => $p['role'] ?? null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
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
            'id_penyelia'                 => 'nullable|integer|exists:users,id',
            'tanggal_mulai'               => 'nullable|date',
            'tanggal_selesai'             => 'nullable|date',
            'waktu_kedatangan'            => 'nullable|date',
            'keterangan'                  => 'nullable|string',
        ]);

        $fwoMulai        = !empty($validated['tanggal_mulai'])   ? substr($validated['tanggal_mulai'], 0, 10) : null;
        $fwoSelesaiCheck = !empty($validated['tanggal_selesai']) ? substr($validated['tanggal_selesai'], 0, 10) : null;
        $waktuKedatangan = !empty($validated['waktu_kedatangan']) ? substr($validated['waktu_kedatangan'], 0, 10) : null;
        if ($fwoMulai && $waktuKedatangan && $waktuKedatangan < $fwoMulai) {
            return response()->json(['message' => 'Waktu kedatangan tidak boleh lebih kecil dari tanggal mulai FWO (' . $fwoMulai . ')'], 422);
        }
        if ($fwoSelesaiCheck && $waktuKedatangan && $waktuKedatangan > $fwoSelesaiCheck) {
            return response()->json(['message' => 'Waktu kedatangan tidak boleh lebih besar dari tanggal selesai FWO (' . $fwoSelesaiCheck . ')'], 422);
        }

        $before = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();

        DB::table('fieldworks')->where('id_fwo', $id)->update([
            ...$validated,
            'updated_at' => now(),
        ]);

        $after = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();
        saveAudit('fieldworks', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Fieldwork berhasil diperbarui']);
    }

    public function updateAttachments(Request $request, $id)
    {
        $request->validate([
            'groups'                   => 'nullable|array',
            'groups.*.type'            => 'required|string|max:100',
            'groups.*.existing'        => 'nullable|array',
            'groups.*.existing.*'      => 'nullable|string',
            'groups.*.files'           => 'nullable|array',
            'groups.*.files.*'         => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:153600',
        ]);

        $groups = $request->input('groups', []);
        $result = [];

        foreach ($groups as $i => $group) {
            $existing = $group['existing'] ?? [];
            $newFiles = [];

            if ($request->hasFile("groups.{$i}.files")) {
                $uploaded = uploadAttachment($request->file("groups.{$i}.files"), 'fieldworks');
                $newFiles = $uploaded['files'];
            }

            $allFiles = array_merge($existing, $newFiles);
            if (empty($allFiles)) continue;

            $result[] = [
                'type'  => $group['type'],
                'files' => $allFiles,
            ];
        }

        DB::table('fieldworks')->where('id_fwo', $id)->update([
            'attachments' => $result ? json_encode($result) : null,
            'updated_at'  => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Attachment berhasil disimpan']);
    }

    public function complete($id)
    {
        if (!userCan('fwo-complete', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk menyelesaikan FWO'], 403);
        }

        $fwo = DB::table('fieldworks')->where('id_fwo', $id)->whereNull('deleted_at')->first();
        if (!$fwo) {
            return response()->json(['message' => 'FWO tidak ditemukan'], 404);
        }
        if ($fwo->status === 'completed') {
            return response()->json(['message' => 'FWO sudah berstatus Completed'], 422);
        }

        $before = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();
        DB::table('fieldworks')->where('id_fwo', $id)->update([
            'status'     => 'completed',
            'updated_at' => now(),
        ]);
        $after = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();
        saveAudit('fieldworks', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'FWO berhasil diselesaikan']);
    }

    public function destroy($id)
    {
        $before = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();
        DB::table('fieldworks')->where('id_fwo', $id)->update(['deleted_at' => now()]);
        $after = DB::table('fieldworks')->where('id_fwo', $id)->get()->toJson();
        saveAudit('fieldworks', $id, 'delete', $before, $after);
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
            'personels'                => 'nullable|array',
            'personels.*.id_personnel' => 'required|integer',
            'personels.*.role'         => 'nullable|string|max:500',
            'sections'              => 'nullable|array',
            'sections.*.id_boq'     => 'required|integer',
            'sections.*.qty'        => 'nullable|integer|min:1',
            'sections.*.keterangan' => 'nullable|string',
            'id_penyelia'           => 'nullable|integer|exists:users,id',
            // Budget Plan yang dipilih user di form clone. Item & account diambil
            // ulang dari DB (cek kepemilikan), hanya nominal & keterangan dari input.
            'budgets'                                  => 'nullable|array',
            'budgets.*.source_id_budget'               => 'required|integer',
            'budgets.*.label'                          => 'required|string|max:255',
            'budgets.*.keterangan'                     => 'nullable|string',
            'budgets.*.items'                          => 'required|array|min:1',
            'budgets.*.items.*.source_id_budget_item'  => 'required|integer',
            'budgets.*.items.*.nominal_budget'         => 'required|integer|min:0',
            'budgets.*.items.*.keterangan'             => 'nullable|string',
        ]);

        // Validasi Budget Plan: harus milik FWO sumber, dan setiap item harus milik Plan itu
        $budgetsToCopy = [];
        foreach ($validated['budgets'] ?? [] as $plan) {
            $sourcePlan = DB::table('fwo_budgets')
                ->where('id_budget', $plan['source_id_budget'])
                ->where('id_fwo', $source->id_fwo)
                ->whereNull('deleted_at')
                ->first();
            if (!$sourcePlan) {
                return response()->json(['message' => "Budget Plan #{$plan['source_id_budget']} tidak ditemukan di FWO ini."], 422);
            }

            $items = [];
            foreach ($plan['items'] as $it) {
                $sourceItem = DB::table('fwo_budget_items')
                    ->where('id_budget_item', $it['source_id_budget_item'])
                    ->where('id_budget', $sourcePlan->id_budget)
                    ->first();
                if (!$sourceItem) {
                    return response()->json(['message' => "Item budget #{$it['source_id_budget_item']} tidak termasuk Plan \"{$sourcePlan->label}\"."], 422);
                }
                $items[] = [
                    'source' => $sourceItem,
                    'nominal_budget' => (int) $it['nominal_budget'],
                    'keterangan' => $it['keterangan'] ?? $sourceItem->keterangan,
                ];
            }

            $budgetsToCopy[] = [
                'label' => $plan['label'],
                'keterangan' => $plan['keterangan'] ?? null,
                'items' => $items,
            ];
        }

        // Validasi tanggal FWO harus dalam range tanggal WO
        $wo = DB::table('work_orders')->where('id_wo', $source->id_wo)->first();
        if ($wo) {
            $woMulai   = $wo->tanggal_mulai   ? substr($wo->tanggal_mulai, 0, 10)   : null;
            $woSelesai = $wo->tanggal_selesai ? substr($wo->tanggal_selesai, 0, 10) : null;
            $fwoMulai   = !empty($validated['tanggal_mulai'])   ? substr($validated['tanggal_mulai'], 0, 10)   : null;
            $fwoSelesai = !empty($validated['tanggal_selesai']) ? substr($validated['tanggal_selesai'], 0, 10) : null;

            if ($fwoMulai && $woMulai && $fwoMulai < $woMulai) {
                return response()->json(['message' => 'Tanggal mulai FWO tidak boleh sebelum tanggal mulai WO (' . $woMulai . ')'], 422);
            }
            if ($fwoMulai && $woSelesai && $fwoMulai > $woSelesai) {
                return response()->json(['message' => 'Tanggal mulai FWO tidak boleh setelah tanggal selesai WO (' . $woSelesai . ')'], 422);
            }
            if ($fwoSelesai && $woMulai && $fwoSelesai < $woMulai) {
                return response()->json(['message' => 'Tanggal selesai FWO tidak boleh sebelum tanggal mulai WO (' . $woMulai . ')'], 422);
            }
            if ($fwoSelesai && $woSelesai && $fwoSelesai > $woSelesai) {
                return response()->json(['message' => 'Tanggal selesai FWO tidak boleh setelah tanggal selesai WO (' . $woSelesai . ')'], 422);
            }
        }

        // Validasi qty semua sections SEBELUM insert apapun
        foreach ($validated['sections'] ?? [] as $sec) {
            if (empty($sec['qty'])) continue;
            $boq = DB::table('boq')->where('id_boq', $sec['id_boq'])->whereNull('deleted_at')->first();
            if (!$boq) {
                return response()->json(['message' => "BOQ #{$sec['id_boq']} tidak ditemukan atau sudah dihapus."], 422);
            }
            $usedQty   = (int) DB::table('fieldwork_boq as fb')
                ->join('fieldworks as fw', 'fw.id_fwo', '=', 'fb.id_fwo')
                ->where('fb.id_boq', $sec['id_boq'])
                ->whereNull('fw.deleted_at')
                ->sum('fb.qty');
            $remaining = (int)($boq->qty ?? 0) - $usedQty;
            if ($sec['qty'] > $remaining) {
                $ptName = DB::table('testing_points')
                    ->where('id_testing_point', $boq->id_testing_point)
                    ->value('nama') ?? "BOQ #{$sec['id_boq']}";
                return response()->json([
                    'message' => "Qty untuk \"{$ptName}\" melebihi sisa yang tersedia (sisa: {$remaining})",
                ], 422);
            }
        }

        $no_fwo = $this->generateNoFwo();

        DB::transaction(function () use ($source, $no_fwo, $validated, $budgetsToCopy, &$newId) {
            $newId = DB::table('fieldworks')->insertGetId([
                'id_wo'                       => $source->id_wo,
                'no_fwo'                      => $no_fwo,
                'judul_pekerjaan'             => $validated['judul_pekerjaan'],
                'id_site_pelanggan_pekerjaan' => $source->id_site_pelanggan_pekerjaan,
                'id_pic_pelanggan_pekerjaan'  => $source->id_pic_pelanggan_pekerjaan,
                'id_penyelia'                 => $validated['id_penyelia'] ?? $source->id_penyelia,
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
                        'id_fwo'       => $newId,
                        'id_personnel' => $p['id_personnel'],
                        'role'         => $p['role'] ?? null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ], $validated['personels'])
                );
            }

            foreach ($validated['sections'] ?? [] as $sec) {
                $boq = DB::table('boq')->where('id_boq', $sec['id_boq'])->whereNull('deleted_at')->first();
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

                $boqItems = DB::table('boq_items')->where('id_boq', $sec['id_boq'])->whereNull('deleted_at')->get();
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

            // Budget Plan terpilih: tanggal dikosongkan, status direset 'open',
            // realisasi & lampiran TIDAK ikut (rencana baru).
            foreach ($budgetsToCopy as $plan) {
                $newBudgetId = DB::table('fwo_budgets')->insertGetId([
                    'id_fwo'            => $newId,
                    'label'             => $plan['label'],
                    'keterangan'        => $plan['keterangan'],
                    'tanggal_mulai'     => null,
                    'tanggal_selesai'   => null,
                    'status'            => 'open',
                    'dokumen_realisasi' => null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                foreach ($plan['items'] as $item) {
                    DB::table('fwo_budget_items')->insert([
                        'id_budget'       => $newBudgetId,
                        'id_account'      => $item['source']->id_account,
                        'nominal_budget'  => $item['nominal_budget'],
                        'keterangan'      => $item['keterangan'],
                        'is_cash_advance' => $item['source']->is_cash_advance,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        });

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

    public function generatePdf($id)
    {
        $fwo = DB::table('fieldworks as fw')
            ->leftJoin('work_orders as wo', 'fw.id_wo', '=', 'wo.id_wo')
            ->leftJoin('sales_orders as so', 'wo.id_so', '=', 'so.id_so')
            // Pelanggan brsFw
            ->leftJoin('business_relation_sites as brsFw', 'brsFw.id_site', '=', 'fw.id_site_pelanggan_pekerjaan')
            // Pelanggan brsWO
            ->leftJoin('business_relation_sites as brsWO', 'brsWO.id_site', '=', 'wo.id_site_pelanggan_pekerjaan')
            ->leftJoin('business_relation_contacts as brc', 'brc.id_contact', '=', 'fw.id_pic_pelanggan_pekerjaan')
            ->leftJoin('users as pny', 'pny.id', '=', 'fw.id_penyelia')

            ->where('fw.id_fwo', $id)
            ->select([
                'fw.*',
                'wo.judul_pekerjaan as wo_judul_pekerjaan',
                'so.no_so',
                'so.no_po',
                'brsFw.nama_lokasi as nama_lokasi_fwo',
                'brsFw.alamat_lengkap as alamat_lengkap_fwo',
                'brsWO.nama_lokasi as nama_lokasi_wo',
                'brsWO.alamat_lengkap as alamat_lengkap_wo',
                'brc.nama_pic',
                'brc.nomor_telepon_pic',
                'pny.name as nama_penyelia',
            ])
            ->first();

        abort_if(!$fwo, 404, 'FWO tidak ditemukan');

        \Carbon\Carbon::setLocale('id');

        $personels = DB::table('fieldwork_personels as fp')
            ->join('personnel as p', 'fp.id_personnel', '=', 'p.id_personnel')
            ->where('fp.id_fwo', $id)
            ->select(['p.nama as name', 'fp.role'])
            ->get();

        $fieldwork_boq = DB::table('fieldwork_boq as fwb')
            ->leftJoin('fieldwork_boq_items as fwbi', 'fwbi.id_fwo_boq', '=', 'fwb.id_fwo_boq')
            ->leftJoin('boq', 'boq.id_boq', '=', 'fwb.id_boq')
            ->leftJoin('testing_points as tp', 'tp.id_testing_point', '=', 'boq.id_testing_point')
            ->leftJoin('testing_standards as ts', 'ts.id_testing_standard', '=', 'tp.id_testing_standard')
            ->leftJoin('testing_matriks_samples as tms', 'tms.id_testing_matriks_sample', '=', 'tp.id_testing_matriks_sample')
            ->leftJoin('testing_items as ti', 'ti.id_testing_item', '=', 'fwbi.id_testing_item')
            ->leftJoin('testing_parameters as tpFw', 'tpFw.id_testing_parameter', '=', 'ti.id_testing_parameter')
            ->leftJoin('satuan as sat', 'sat.id_satuan', '=', 'boq.id_satuan')
            ->where('fwb.id_fwo', $id)
            ->whereNull('fwb.deleted_at')
            ->select(
                'tms.kode as kode_tms',
                'tms.judul_indonesia as judul_indonesia_tms',
                'tms.judul_inggris as judul_inggris_tms',
                'ts.nomor as nomor_ts',
                'tp.nama as nama_tp',
                'tp.nomor_halaman as nomor_halaman_tp',
                'fwb.qty',
                'sat.nama as satuan',
                'tpFw.kode as kode_tpfw',
                'tpFw.judul_indonesia as judul_indonesia_tpfw',
                'tpFw.judul_inggris as judul_inggris_tpfw',
            )
            ->get();
        $boqGroups = $fieldwork_boq->groupBy(function ($row) {
            return $row->kode_tms . '||' . $row->nama_tp . '||' . $row->qty;
        })->map(function ($rows) {
            $first = $rows->first();
            return (object) [
                'kode_tms'          => $first->kode_tms,
                'judul_indonesia_tms' => $first->judul_indonesia_tms,
                'judul_inggris_tms' => $first->judul_inggris_tms,
                'nomor_ts'          => $first->nomor_ts,
                'nama_tp'           => $first->nama_tp,
                'nomor_halaman_tp'  => $first->nomor_halaman_tp,
                'qty'               => $first->qty,
                'satuan'               => $first->satuan,
                'items'             => $rows->map(function ($r) {
                    return (object) [
                        'kode'             => $r->kode_tpfw,
                        'judul_indonesia'  => $r->judul_indonesia_tpfw,
                        'judul_inggris'    => $r->judul_inggris_tpfw,
                    ];
                })->values(),
            ];
        })->values();

        return Pdf::view('pdf.fwo.index', compact('fwo', 'personels', 'boqGroups'))
            ->headerView('pdf.layouts.sections.header')
            ->footerView('pdf.layouts.sections.footer')
            ->margins(top: 30, right: 0, bottom: 20, left: 0)
            ->format('a4')
            ->name("FWO-{$fwo->no_fwo}.pdf");
    }
}
