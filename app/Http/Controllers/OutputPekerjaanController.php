<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutputPekerjaanController extends Controller
{
    public function byWo($id_wo)
    {
        $outputs = DB::table('output_pekerjaan')
            ->where('id_wo', $id_wo)
            ->orderBy('id_output')
            ->get();

        return response()->json($outputs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_wo'          => 'required|integer',
            'judul_output'   => 'required|string|max:255',
            'judul_dokumen'  => 'nullable|string|max:255',
            'jenis_dokumen'  => 'nullable|in:copy,asli,asli_dan_copy',
            'qty_copy'       => 'nullable|integer|min:0',
            'qty_asli'       => 'nullable|integer|min:0',
            'link_drive'     => 'nullable|string|max:2048',
            'status'         => 'nullable|in:belum_siap,siap',
            'tanggal_mulai'  => 'nullable|date',
            'tanggal_selesai'=> 'nullable|date|after_or_equal:tanggal_mulai',
            'attachments.*'  => 'nullable|file|max:153600',
        ]);

        $files = [];
        if ($request->hasFile('attachments')) {
            $upload = uploadAttachment($request->file('attachments'), 'output_pekerjaan');
            $files  = $upload['files'];
        }

        // Status 'siap' cuma boleh diset lewat aksi khusus (slug
        // 'output-pekerjaan-siap') — kalau dikirim langsung tanpa akses itu,
        // turunkan diam-diam ke 'belum_siap' supaya tidak jadi celah bypass.
        $status = $request->status ?? 'belum_siap';
        if ($status === 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            $status = 'belum_siap';
        }

        $id = DB::table('output_pekerjaan')->insertGetId([
            'id_wo'           => $request->id_wo,
            'judul_output'    => $request->judul_output,
            'judul_dokumen'   => $request->judul_dokumen,
            'jenis_dokumen'   => $request->jenis_dokumen,
            'qty_copy'        => in_array($request->jenis_dokumen, ['copy','asli_dan_copy']) ? ($request->qty_copy ?: null) : null,
            'qty_asli'        => in_array($request->jenis_dokumen, ['asli','asli_dan_copy']) ? ($request->qty_asli ?: null) : null,
            'link_drive'      => $request->link_drive ?: null,
            'status'          => $status,
            'tanggal_mulai'   => $request->tanggal_mulai ?: null,
            'tanggal_selesai' => $request->tanggal_selesai ?: null,
            'attachments'     => $files ? json_encode($files) : null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => DB::table('output_pekerjaan')->where('id_output', $id)->first(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'judul_output'   => 'required|string|max:255',
            'judul_dokumen'  => 'nullable|string|max:255',
            'jenis_dokumen'  => 'nullable|in:copy,asli,asli_dan_copy',
            'qty_copy'       => 'nullable|integer|min:0',
            'qty_asli'       => 'nullable|integer|min:0',
            'link_drive'      => 'nullable|string|max:2048',
            'status'          => 'nullable|in:belum_siap,siap',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'attachments.*'   => 'nullable|file|max:153600',
        ]);

        $existing = $request->existing_attachments ?? [];
        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $upload   = uploadAttachment($request->file('attachments'), 'output_pekerjaan');
            $newFiles = $upload['files'];
        }
        $allFiles = array_merge($existing, $newFiles);

        // Transisi ke 'siap' cuma boleh lewat aksi khusus (slug
        // 'output-pekerjaan-siap') — kalau form edit biasa coba ubah ke
        // 'siap' tanpa akses itu, pertahankan status lama (field lain tetap
        // ikut ter-update seperti biasa, bukan seluruh request ditolak).
        $current = DB::table('output_pekerjaan')->where('id_output', $id)->value('status');
        $status  = $request->status ?? 'belum_siap';
        if ($status === 'siap' && $current !== 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            $status = $current;
        }

        DB::table('output_pekerjaan')->where('id_output', $id)->update([
            'judul_output'  => $request->judul_output,
            'judul_dokumen' => $request->judul_dokumen,
            'jenis_dokumen' => $request->jenis_dokumen,
            'qty_copy'      => in_array($request->jenis_dokumen, ['copy','asli_dan_copy']) ? ($request->qty_copy ?: null) : null,
            'qty_asli'      => in_array($request->jenis_dokumen, ['asli','asli_dan_copy']) ? ($request->qty_asli ?: null) : null,
            'link_drive'      => $request->link_drive ?: null,
            'status'          => $status,
            'tanggal_mulai'   => $request->tanggal_mulai ?: null,
            'tanggal_selesai' => $request->tanggal_selesai ?: null,
            'attachments'     => $allFiles ? json_encode($allFiles) : null,
            'updated_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => DB::table('output_pekerjaan')->where('id_output', $id)->first(),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:belum_siap,siap,terkirim']);

        if ($request->status === 'siap' && !userCan('output-pekerjaan-siap', 'can_update')) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk aksi Siap Output Pekerjaan'], 403);
        }

        DB::table('output_pekerjaan')->where('id_output', $id)->update([
            'status'     => $request->status,
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true, 'status' => $request->status]);
    }

    public function destroy($id)
    {
        DB::table('output_pekerjaan')->where('id_output', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Output berhasil dihapus']);
    }
}
