<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Traits\HasAuditHistory;

class TerminController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'termin';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_termin'];
    }

    public function index()
    {
        return view('termin.index', [
            'title' => 'Termin'
        ]);
    }

    public function data(Request $request)
    {
        $filters     = $request->input('filters', []);
        $isSearching = !empty($filters);

        $query = DB::table('termin as t')
            ->select([
                'id_termin',
                'status',
                'no_termin',
                'nama',
                'persentase',
                'nilai',
                'tanggal',
            ])
            ->when(!$isSearching, fn($q) => $q->where('t.status', '!=', 'selesai'))
            ->when($isSearching, function ($q) use ($filters) {
                foreach ($filters as $f) {
                    $by   = $f['by'] ?? '';
                    $term = $f['q']  ?? '';
                    if (!$by || !$term) continue;
                    match ($by) {
                        'no_termin' => $q->where('t.no_termin', 'like', "%{$term}%"),
                        'nama'      => $q->where('t.nama', 'like', "%{$term}%"),
                        'status'    => match ($term) {
                            'all'    => null,
                            default  => $q->where('t.status', $term),
                        },
                        default => null,
                    };
                }
            })
            ->orderBy('id_termin', 'desc')
            ->get()
            ->map(function ($row) {
                $row->tanggal    = $row->tanggal ? date('d/m/Y', strtotime($row->tanggal)) : '-';
                $row->nilai      = number_format($row->nilai, 0, ',', '.');
                $row->persentase = $row->persentase . '%';
                return $row;
            });

        return response()->json(['data' => $query->values()]);
    }

    public function create(Request $request)
    {
        if (!$request->filled('id_so')) {
            return redirect()->route('termin.index');
        }

        return view('termin.create', [
            'title' => 'Create Termin'
        ]);
    }

    /**
     * Snapshot Termin untuk audit log: baris termin + satu field per output yang ditagihkan
     * ("Output • judul" => judul tagihan) supaya History hanya menampilkan output yang berubah.
     */
    private function terminJson($id): string
    {
        $termin = DB::table('termin')->where('id_termin', $id)->first();
        if (!$termin) return '';

        $termin = (array) $termin;
        unset($termin['attachment']);

        $tambah = function (string $awalan, $nama, $tagihan) use (&$termin) {
            $kunci = $awalan . ' • ' . ($nama ?: '?');
            for ($n = 2; isset($termin[$kunci]); $n++) $kunci = $awalan . ' • ' . ($nama ?: '?') . " ($n)";
            $termin[$kunci] = $tagihan ?: 'ditagihkan';
        };

        foreach (DB::table('output_pekerjaan')->where('id_termin', $id)->orderBy('id_output')->get(['judul_output', 'judul_tagihan']) as $o) {
            $tambah('Output', $o->judul_output, $o->judul_tagihan);
        }
        if ($this->otherReady()) {
            foreach (DB::table('output_tambahan')->where('id_termin', $id)->whereNull('deleted_at')->orderBy('id_output_tambahan')->get(['nama_item', 'judul_tagihan']) as $o) {
                $tambah('Output Other', $o->nama_item, $o->judul_tagihan);
            }
        }

        return json_encode([$termin]);
    }

    /** Induk SO dari Termin (log Termin ikut tampil di History SO, tab Termin). */
    private function parentSo($idTermin): ?array
    {
        $idSo = DB::table('termin')->where('id_termin', $idTermin)->value('id_so');
        return $idSo ? ['sales_orders', $idSo] : null;
    }

    /** Catat aksi pada Termin (output ditambah/dilepas, status) bila snapshot berubah. */
    private function auditTermin($id, string $aksi, string $before): void
    {
        saveAudit('termin', $id, $aksi, $before, $this->terminJson($id), $this->parentSo($id));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'          => 'required|string|max:255',
            'persentase'    => 'nullable|numeric|min:0|max:100',
            'nilai'         => 'required|numeric|min:0',
            'tanggal'       => 'required|date',
            'status'        => 'required|in:pending,siap_kirim,selesai',
            'keterangan'    => 'nullable|string',
            'id_so'         => 'nullable|integer',
        ]);

        if ($request->boolean('is_dp') && (!empty($request->input('selected_outputs', [])) || !empty($request->input('selected_other', [])))) {
            return response()->json([
                'message' => 'Termin Down Payment (DP) tidak dapat memilih Output Pekerjaan.',
            ], 422);
        }

        $status = $this->guardTerminStatus($request->status, 'pending');

        $id = DB::table('termin')->insertGetId([
            'id_so'      => $request->id_so ?: null,
            'no_termin'  => $this->generateNoTermin(),
            'nama'       => $request->nama,
            'persentase' => $request->persentase,
            'nilai'      => $request->nilai,
            'tanggal'    => $request->tanggal,
            'status'     => $status,
            'is_dp'      => $request->boolean('is_dp') ? 1 : 0,
            'keterangan' => $request->keterangan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $selectedOutputs = $request->input('selected_outputs', []);
        $judulTagihan    = $request->input('judul_tagihan', []);
        foreach ($selectedOutputs as $idOutput) {
            DB::table('output_pekerjaan')->where('id_output', $idOutput)->update([
                'id_termin'     => $id,
                'judul_tagihan' => $judulTagihan[$idOutput] ?? null,
                'updated_at'    => now(),
            ]);
        }

        $this->claimOther((int) $id, $request->id_so, $request->input('selected_other', []), $judulTagihan);

        saveAudit('termin', $id, 'Create', '', $this->terminJson($id), $this->parentSo($id));

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function show($id)
    {
        $data = DB::table('termin as t')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 't.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->where('t.id_termin', $id)
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->select('t.*', 'so.no_so', 'so.judul_order', 'br.nama as nama_pelanggan_billing', 'ent.nama as entitas_pelanggan_billing')
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data->nama_pelanggan_billing = brDisplayName($data->entitas_pelanggan_billing, $data->nama_pelanggan_billing);

        $data->assigned_outputs = $this->assignedOutputs((int) $id);

        return response()->json($data);
    }

    public function detail($id)
    {
        $data = DB::table('termin as t')
            ->leftJoin('sales_orders as so', 'so.id_so', '=', 't.id_so')
            ->leftJoin('business_relations as br', 'br.id_br', '=', 'so.id_pelanggan')
            ->where('t.id_termin', $id)
            ->leftJoin('entitas as ent', 'ent.id_entitas', '=', 'br.id_entitas')
            ->select('t.*', 'so.no_so', 'so.judul_order', 'br.nama as nama_pelanggan_billing', 'ent.nama as entitas_pelanggan_billing')
            ->first();

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $data->nama_pelanggan_billing = brDisplayName($data->entitas_pelanggan_billing, $data->nama_pelanggan_billing);

        $data->assigned_outputs = $this->assignedOutputs((int) $id);

        return response()->json($data);
    }

    public function addOutput(Request $request, $id)
    {
        $termin = DB::table('termin')->where('id_termin', $id)->first();
        if ($termin && $termin->is_dp) {
            return response()->json([
                'message' => 'Termin Down Payment (DP) tidak dapat memilih Output Pekerjaan.',
            ], 422);
        }

        $before = $this->terminJson($id);
        $ids = array_map('intval', $request->input('ids', []));
        foreach ($ids as $idOutput) {
            DB::table('output_pekerjaan')
                ->where('id_output', $idOutput)
                ->update(['id_termin' => $id, 'updated_at' => now()]);
        }
        $this->claimOther((int) $id, $termin->id_so ?? null, $request->input('ids_other', []));
        $this->auditTermin($id, 'update', $before);
        return response()->json(['success' => true]);
    }

    public function removeOutput(Request $request, $id)
    {
        $before = $this->terminJson($id);
        $idOutput = (int) $request->input('id_output');
        DB::table('output_pekerjaan')
            ->where('id_output', $idOutput)
            ->where('id_termin', $id)
            ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);

        // Output Other (output_tambahan) dilepas lewat id terpisah
        if ($request->filled('id_output_other') && $this->otherReady()) {
            DB::table('output_tambahan')
                ->where('id_output_tambahan', (int) $request->input('id_output_other'))
                ->where('id_termin', $id)
                ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);
        }
        $this->auditTermin($id, 'update', $before);
        return response()->json(['success' => true]);
    }

    public function siapKirim($id)
    {
        if (!userCan('termin-siap-kirim', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk aksi Siap Kirim Termin'], 403);
        }

        $before = $this->terminJson($id);
        DB::table('termin')->where('id_termin', $id)->update([
            'status'     => 'siap_kirim',
            'updated_at' => now(),
        ]);
        $this->auditTermin($id, 'update', $before);
        return response()->json(['success' => true]);
    }

    public function selesai($id)
    {
        if (!userCan('termin-selesai', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk aksi Selesaikan Termin'], 403);
        }

        $before = $this->terminJson($id);
        DB::table('termin')->where('id_termin', $id)->update([
            'status'     => 'selesai',
            'updated_at' => now(),
        ]);
        $this->auditTermin($id, 'update', $before);
        return response()->json(['success' => true]);
    }

    /**
     * Transisi status Termin ke 'siap_kirim'/'selesai' cuma boleh lewat aksi
     * khusus (siapKirim()/selesai()) — kalau dikirim langsung lewat
     * store()/update() biasa tanpa slug aksi terkait, turunkan ke status
     * sebelumnya (create: 'pending') supaya tidak jadi celah bypass.
     */
    private function guardTerminStatus(string $requestedStatus, string $fallback): string
    {
        if ($requestedStatus === 'siap_kirim' && $fallback !== 'siap_kirim' && !userCan('termin-siap-kirim', 'can_update')) {
            return $fallback;
        }
        if ($requestedStatus === 'selesai' && $fallback !== 'selesai' && !userCan('termin-selesai', 'can_update')) {
            return $fallback;
        }
        return $requestedStatus;
    }

    public function checkDpBySo($id_so, Request $request)
    {
        $query = DB::table('termin')->where('id_so', $id_so)->where('is_dp', 1);
        if ($request->filled('exclude_id')) {
            $query->where('id_termin', '!=', $request->exclude_id);
        }
        $exists = $query->exists();
        return response()->json(['has_dp' => $exists]);
    }

    public function outputsBySo($id_so)
    {
        $outputs = DB::table('output_pekerjaan as op')
            ->join('work_orders as wo', 'wo.id_wo', '=', 'op.id_wo')
            ->where('wo.id_so', $id_so)
            ->whereNull('wo.deleted_at')
            ->where(function ($q) {
                // Tampilkan output yang belum ditugaskan ATAU yang terminnya sudah dihapus
                $q->whereNull('op.id_termin')
                  ->orWhereNotExists(function ($sub) {
                      $sub->select(DB::raw(1))
                          ->from('termin')
                          ->whereColumn('termin.id_termin', 'op.id_termin');
                  });
            })
            ->select('op.id_output', 'op.judul_output', 'op.judul_dokumen', 'op.status', 'op.tanggal_selesai', 'op.attachments', 'op.link_drive', 'wo.no_wo', 'wo.id_wo')
            ->orderBy('wo.no_wo')
            ->orderBy('op.id_output')
            ->get();

        $outputs->each(fn ($o) => $o->tipe = 'pekerjaan');

        // Output Other (output_tambahan): hanya yang berstatus Siap & belum ditagihkan
        if ($this->otherReady()) {
            // Milik WO dari SO ini, atau milik SO ini langsung (Output Other level SO, no_wo = null)
            $other = DB::table('output_tambahan as ot')
                ->leftJoin('work_orders as wo', function ($j) {
                    $j->on('wo.id_wo', '=', 'ot.id_wo')->whereNull('wo.deleted_at');
                })
                ->where(function ($q) use ($id_so) {
                    $q->where('wo.id_so', $id_so);
                    if ($this->soReady()) $q->orWhere('ot.id_so', $id_so);
                })
                ->whereNull('ot.deleted_at')
                ->where('ot.status', 'siap')
                ->whereNull('ot.id_termin')
                ->orderBy('wo.no_wo')
                ->orderBy('ot.id_output_tambahan')
                ->get([
                    'ot.id_output_tambahan as id_output', 'ot.nama_item as judul_output',
                    'ot.status', 'ot.tanggal_selesai', 'ot.attachments', 'ot.link_drive', 'wo.no_wo', 'wo.id_wo',
                ]);
            $other->each(fn ($o) => $o->tipe = 'other');
            // Level SO (tanpa WO) diurutkan paling akhir
            $outputs = $outputs->concat($other)->sortBy(fn ($o) => $o->no_wo ?? "\u{10FFFF}")->values();
        }

        return response()->json($outputs);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama'       => 'required|string|max:255',
            'persentase' => 'nullable|numeric|min:0|max:100',
            'nilai'      => 'required|numeric|min:0',
            'tanggal'    => 'required|date',
            'status'     => 'required|in:pending,siap_kirim,selesai',
            'keterangan' => 'nullable|string',
            'id_so'      => 'nullable|integer',
        ]);

        if ($request->boolean('is_dp') && (!empty($request->input('selected_outputs', [])) || !empty($request->input('selected_other', [])))) {
            return response()->json([
                'message' => 'Termin Down Payment (DP) tidak dapat memilih Output Pekerjaan.',
            ], 422);
        }

        $before = $this->terminJson($id);

        $currentStatus = DB::table('termin')->where('id_termin', $id)->value('status');
        $status = $this->guardTerminStatus($request->status, $currentStatus);

        DB::table('termin')->where('id_termin', $id)->update([
            'id_so'      => $request->id_so ?: null,
            'nama'       => $request->nama,
            'persentase' => $request->persentase,
            'nilai'      => $request->nilai,
            'tanggal'    => $request->tanggal,
            'status'     => $status,
            'is_dp'      => $request->boolean('is_dp') ? 1 : 0,
            'keterangan' => $request->keterangan,
            'updated_at' => now(),
        ]);

        // Sinkronisasi output pekerjaan
        $selectedOutputs = array_map('intval', $request->input('selected_outputs', []));
        $judulTagihan    = $request->input('judul_tagihan', []);

        $currentOutputIds = DB::table('output_pekerjaan')
            ->where('id_termin', $id)
            ->pluck('id_output')
            ->toArray();

        $toRemove = array_diff($currentOutputIds, $selectedOutputs);
        if ($toRemove) {
            DB::table('output_pekerjaan')
                ->whereIn('id_output', $toRemove)
                ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);
        }

        foreach ($selectedOutputs as $idOutput) {
            DB::table('output_pekerjaan')->where('id_output', $idOutput)->update([
                'id_termin'     => $id,
                'judul_tagihan' => $judulTagihan[$idOutput] ?? null,
                'updated_at'    => now(),
            ]);
        }

        // Sinkronisasi Output Other
        if ($this->otherReady()) {
            $selectedOther = array_map('intval', $request->input('selected_other', []));
            DB::table('output_tambahan')
                ->where('id_termin', $id)
                ->when($selectedOther, fn ($q) => $q->whereNotIn('id_output_tambahan', $selectedOther))
                ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);
            $this->claimOther((int) $id, $request->id_so, $selectedOther, $judulTagihan);
        }

        saveAudit('termin', $id, 'update', $before, $this->terminJson($id), $this->parentSo($id));

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $before = $this->terminJson($id);
        $parent = $this->parentSo($id);

        // Lepaskan output yang ditugaskan agar bisa ditagihkan kembali
        DB::table('output_pekerjaan')
            ->where('id_termin', $id)
            ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);

        if ($this->otherReady()) {
            DB::table('output_tambahan')
                ->where('id_termin', $id)
                ->update(['id_termin' => null, 'judul_tagihan' => null, 'updated_at' => now()]);
        }

        DB::table('termin')->where('id_termin', $id)->delete();
        saveAudit('termin', $id, 'delete', $before, '', $parent);

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function bySo(int $id_so)
    {
        $rows = DB::table('termin')
            ->where('id_so', $id_so)
            ->orderBy('id_termin')
            ->select(['id_termin', 'no_termin', 'nama', 'persentase', 'nilai', 'tanggal', 'status', 'created_at'])
            ->get();

        return response()->json($rows->values());
    }

    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('termin')
            ->where('nomor', 'like', "%{$search}%")
            ->orWhere('nama', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(fn($item) => [
                'id'   => $item->id_termin,
                'text' => $item->nomor . ' - ' . $item->nama,
            ])
        );
    }

    /** Kolom id_termin di output_tambahan baru ada setelah migration dijalankan. */
    private function otherReady(): bool
    {
        return Schema::hasColumn('output_tambahan', 'id_termin');
    }

    /** Kolom id_so (Output Other level SO) baru ada setelah migration 2026_10_26_000002 dijalankan. */
    private function soReady(): bool
    {
        return Schema::hasColumn('output_tambahan', 'id_so');
    }

    /**
     * Pasang Output Other ke Termin. Hanya yang milik WO dari SO yang sama, berstatus Siap,
     * dan belum ditagihkan (atau sudah milik Termin ini) yang diterima; id lain diabaikan.
     */
    private function claimOther(int $idTermin, $idSo, array $ids, array $judulTagihan = []): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids || !$idSo || !$this->otherReady()) return;

        $valid = DB::table('output_tambahan as ot')
            ->leftJoin('work_orders as wo', function ($j) {
                $j->on('wo.id_wo', '=', 'ot.id_wo')->whereNull('wo.deleted_at');
            })
            ->where(function ($q) use ($idSo) {
                $q->where('wo.id_so', $idSo);
                if ($this->soReady()) $q->orWhere('ot.id_so', $idSo); // Output Other level SO
            })
            ->whereNull('ot.deleted_at')
            ->whereIn('ot.id_output_tambahan', $ids)
            ->where(function ($q) use ($idTermin) {
                $q->where('ot.id_termin', $idTermin)
                  ->orWhere(fn ($n) => $n->whereNull('ot.id_termin')->where('ot.status', 'siap'));
            })
            ->pluck('ot.id_output_tambahan');

        foreach ($valid as $idOther) {
            DB::table('output_tambahan')->where('id_output_tambahan', $idOther)->update([
                'id_termin'     => $idTermin,
                'judul_tagihan' => $judulTagihan[$idOther] ?? null,
                'updated_at'    => now(),
            ]);
        }
    }

    /** Output yang sudah ditagihkan di Termin: Output Pekerjaan + Output Other. */
    private function assignedOutputs(int $idTermin)
    {
        $rows = DB::table('output_pekerjaan as op')
            ->leftJoin('work_orders as wo', 'wo.id_wo', '=', 'op.id_wo')
            ->where('op.id_termin', $idTermin)
            ->select('op.id_output', 'op.id_wo', 'op.judul_output', 'op.judul_tagihan', 'op.status', 'op.attachments', 'op.link_drive', 'wo.no_wo')
            ->orderBy('wo.no_wo')
            ->orderBy('op.id_output')
            ->get();
        $rows->each(fn ($o) => $o->tipe = 'pekerjaan');

        if ($this->otherReady()) {
            $other = DB::table('output_tambahan as ot')
                ->leftJoin('work_orders as wo', 'wo.id_wo', '=', 'ot.id_wo')
                ->where('ot.id_termin', $idTermin)
                ->whereNull('ot.deleted_at')
                ->select('ot.id_output_tambahan as id_output', 'ot.id_wo', 'ot.nama_item as judul_output', 'ot.judul_tagihan', 'ot.status', 'ot.attachments', 'ot.link_drive', 'wo.no_wo')
                ->orderBy('wo.no_wo')
                ->orderBy('ot.id_output_tambahan')
                ->get();
            $other->each(fn ($o) => $o->tipe = 'other');
            $rows = $rows->concat($other)->sortBy(fn ($o) => $o->no_wo ?? "\u{10FFFF}")->values();
        }

        return $rows;
    }

    private function generateNoTermin(): string
    {
        $year   = now()->format('y');
        $prefix = "ST-{$year}-";

        $latest = DB::table('termin')
            ->whereNotNull('no_termin')
            ->orderByDesc('id_termin')
            ->value('no_termin');

        if (!$latest) {
            return $prefix . '00001';
        }

        $parts  = explode('-', $latest);
        $number = (int) end($parts) + 1;
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
