<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasAuditHistory;

class BrsSamplingPointController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'brs_sampling_points';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_sp'];
    }

    public function select2BySite($id_site)
    {
        $rows = DB::table('brs_sampling_points')
            ->where('id_site', $id_site)
            ->where('is_aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get(['id_sp', 'jenis', 'kode', 'nama']);

        $results = $rows->map(fn($r) => [
            'id'   => $r->id_sp,
            'text' => "[{$r->jenis}] " . ($r->kode ? "{$r->kode} – " : '') . $r->nama,
        ]);

        return response()->json($results);
    }

    public function data(Request $request, $id_site)
    {
        $jenis = $request->input('jenis'); // 'env' atau 'we'

        $rows = DB::table('brs_sampling_points')
            ->where('id_site', $id_site)
            ->where('jenis', $jenis)
            ->whereNull('deleted_at')
            ->orderBy('id_sp')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function show($id)
    {
        $row = DB::table('brs_sampling_points')->where('id_sp', $id)->first();

        if (!$row) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($row);
    }

    public function store(Request $request)
    {
        $jenis = $request->input('jenis');

        $rules = [
            'id_site'    => 'required|integer',
            'jenis'      => 'required|in:env,we',
            'kode'       => 'required|string|max:255',
            'nama'       => 'required|string|max:255',
            'gedung'     => 'nullable|string|max:255',
            'ruangan'    => 'nullable|string|max:255',
            'lantai'     => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
            'is_aktif'   => 'nullable|boolean',
        ];

        // Koordinat opsional untuk ENV maupun WE (sejak 2026-10-14); kalau salah satu diisi, pasangannya wajib
        $rules['latitude']  = 'nullable|required_with:longitude|numeric|between:-90,90';
        $rules['longitude'] = 'nullable|required_with:latitude|numeric|between:-180,180';

        $request->validate($rules);

        $kodeExists = DB::table('brs_sampling_points')
            ->where('id_site', $request->id_site)
            ->where('kode', $request->kode)
            ->whereNull('deleted_at')
            ->exists();

        if ($kodeExists) {
            return response()->json([
                'message' => 'Kode sudah digunakan pada site ini.',
                'errors'  => ['kode' => ['Kode sudah digunakan pada site ini.']],
            ], 422);
        }

        $id = DB::table('brs_sampling_points')->insertGetId([
            'id_site'    => $request->id_site,
            'jenis'      => $request->jenis,
            'kode'       => $request->kode,
            'nama'       => $request->nama,
            'latitude'   => $request->latitude ?: null,
            'longitude'  => $request->longitude ?: null,
            'gedung'     => $request->gedung ?: null,
            'ruangan'    => $request->ruangan ?: null,
            'lantai'     => $request->lantai ?: null,
            'keterangan' => $request->keterangan ?: null,
            'is_aktif'   => $request->boolean('is_aktif', true) ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $after = DB::table('brs_sampling_points')->where('id_sp', $id)->get()->toJson();
        saveAudit('brs_sampling_points', $id, 'Create', '', $after);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function update(Request $request, $id)
    {
        $row = DB::table('brs_sampling_points')->where('id_sp', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $jenis = $row->jenis;

        $rules = [
            'kode'       => 'required|string|max:255',
            'nama'       => 'required|string|max:255',
            'gedung'     => 'nullable|string|max:255',
            'ruangan'    => 'nullable|string|max:255',
            'lantai'     => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
            'is_aktif'   => 'nullable|boolean',
        ];

        // Koordinat opsional untuk ENV maupun WE; kalau salah satu diisi, pasangannya wajib
        $rules['latitude']  = 'nullable|required_with:longitude|numeric|between:-90,90';
        $rules['longitude'] = 'nullable|required_with:latitude|numeric|between:-180,180';

        $request->validate($rules);

        $kodeExists = DB::table('brs_sampling_points')
            ->where('id_site', $row->id_site)
            ->where('kode', $request->kode)
            ->where('id_sp', '!=', $id)
            ->whereNull('deleted_at')
            ->exists();

        if ($kodeExists) {
            return response()->json([
                'message' => 'Kode sudah digunakan pada site ini.',
                'errors'  => ['kode' => ['Kode sudah digunakan pada site ini.']],
            ], 422);
        }

        $before = DB::table('brs_sampling_points')->where('id_sp', $id)->get()->toJson();

        DB::table('brs_sampling_points')->where('id_sp', $id)->update([
            'kode'       => $request->kode,
            'nama'       => $request->nama,
            'latitude'   => $request->latitude ?: null,
            'longitude'  => $request->longitude ?: null,
            'gedung'     => $request->gedung ?: null,
            'ruangan'    => $request->ruangan ?: null,
            'lantai'     => $request->lantai ?: null,
            'keterangan' => $request->keterangan ?: null,
            'is_aktif'   => $request->boolean('is_aktif', true) ? 1 : 0,
            'updated_at' => now(),
        ]);

        $after = DB::table('brs_sampling_points')->where('id_sp', $id)->get()->toJson();
        saveAudit('brs_sampling_points', $id, 'Update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $row = DB::table('brs_sampling_points')->where('id_sp', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $before = DB::table('brs_sampling_points')->where('id_sp', $id)->get()->toJson();

        DB::table('brs_sampling_points')->where('id_sp', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        saveAudit('brs_sampling_points', $id, 'Delete', $before, '');

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function history($id)
    {
        return $this->auditHistory($id);
    }
}
