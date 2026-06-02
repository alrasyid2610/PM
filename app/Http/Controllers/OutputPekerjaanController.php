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
            'attachments.*'  => 'nullable|file|max:10240',
        ]);

        $files = [];
        if ($request->hasFile('attachments')) {
            $upload = uploadAttachment($request->file('attachments'), 'output_pekerjaan');
            $files  = $upload['files'];
        }

        $id = DB::table('output_pekerjaan')->insertGetId([
            'id_wo'         => $request->id_wo,
            'judul_output'  => $request->judul_output,
            'judul_dokumen' => $request->judul_dokumen,
            'attachments'   => $files ? json_encode($files) : null,
            'created_at'    => now(),
            'updated_at'    => now(),
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
            'attachments.*'  => 'nullable|file|max:10240',
        ]);

        $existing = $request->existing_attachments ?? [];
        $newFiles = [];
        if ($request->hasFile('attachments')) {
            $upload   = uploadAttachment($request->file('attachments'), 'output_pekerjaan');
            $newFiles = $upload['files'];
        }
        $allFiles = array_merge($existing, $newFiles);

        DB::table('output_pekerjaan')->where('id_output', $id)->update([
            'judul_output'  => $request->judul_output,
            'judul_dokumen' => $request->judul_dokumen,
            'attachments'   => $allFiles ? json_encode($allFiles) : null,
            'updated_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => DB::table('output_pekerjaan')->where('id_output', $id)->first(),
        ]);
    }

    public function destroy($id)
    {
        DB::table('output_pekerjaan')->where('id_output', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Output berhasil dihapus']);
    }
}
