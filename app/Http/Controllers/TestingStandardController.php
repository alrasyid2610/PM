<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\Environment\Console;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;
use App\Traits\HasAttachment;

class TestingStandardController extends Controller
{
    use HasAuditHistory, HasAttachment;

    protected function attachmentTable(): string      { return 'testing_standards'; }
    protected function attachmentPrimaryKey(): string { return 'id_testing_standard'; }

    protected function auditTable(): string
    {
        return 'testing_standards';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_testing_standard'];
    }
    public function index()
    {
        return view('testing-standards.index', [
            'title' => 'Testing Standards'
        ]);
    }

    public function data()
    {
        $query = DB::table('testing_standards')->select([
            'id_testing_standard',
            'nomor',
            'judul',
            'is_aktif',
            'attachment',
            'created_at'
        ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('is_aktif', function ($row) {
                return $row->is_aktif ? 'Aktif' : 'Tidak Aktif';
            })
            ->make(true);
    }

    public function create()
    {
        return view('testing-standards.create', [
            'title' => 'Create Testing Standard'
        ]);
    }

    public function show($id)
    {
        $data = DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }


    public function select2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('testing_standards')
            ->where('nomor', 'like', "%{$search}%")
            ->orWhere('judul', 'like', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id_testing_standard,
                    'text' => $item->nomor . ' - ' . $item->judul,
                ];
            })
        );
    }

    public function store(Request $request)
    {


        $table = 'testing_standards';
        $validated = $request->validate([
            'nomor' => 'required|string|max:100',
            'judul' => 'required|string|max:255',
            'is_aktif' => 'required|boolean',
            'attachments.*' => 'nullable|file|max:5120'
        ]);

        $upload = uploadAttachment($request->file('attachments'), $table);
        $files = $upload['files'];

        $id = DB::table('testing_standards')->insertGetId([
            'nomor' => $request->nomor,
            'judul' => $request->judul,
            'is_aktif' => $request->is_aktif,
            'attachment' => json_encode($files),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $after = DB::table('testing_standards')->where('id_testing_standard', $id)->get()->toJson();
        saveAudit('testing_standards', $id, 'Create', '', $after);

        return response()->json([
            'success' => true,
            'id' => $id
        ]);
    }
    public function detail($id)
    {
        $data = DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {

        // dd($request->all());

        $validated = $request->validate([
            'nomor' => 'required|string|max:50',
            'judul' => 'required|string|max:255',
            'is_aktif' => 'required|boolean',
        ]);



        $data = DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->get();

        $before = $data->toJson();

        $existing = $request->existing_attachments ?? [];
        $newFiles = [];

        if ($request->hasFile('attachments')) {

            $upload = uploadAttachment(
                $request->file('attachments'),
                'testing_standards'
            );

            $newFiles = $upload['files'];
        }

        $attachments = array_merge($existing, $newFiles);

        DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->update([
                'nomor' => $validated['nomor'],
                'judul' => $validated['judul'],
                'is_aktif' => $validated['is_aktif'],
                'attachment' => json_encode($attachments),
                'updated_at' => now(),
            ]);

        $after = DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->get()->toJson();

        saveAudit(
            'testing_standards',
            $id,
            'update',
            $before,
            $after
        );

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui'
        ]);
    }

    public function destroy($id)
    {
        DB::table('testing_standards')
            ->where('id_testing_standard', $id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }


}
