<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use App\Traits\HasAuditHistory;

class DocumentationController extends Controller
{
    use HasAuditHistory;

    protected function auditTable(): string
    {
        return 'documentations';
    }

    protected function auditExcludeFields(): array
    {
        return ['updated_at', 'created_at', 'id_documentation'];
    }

    /**
     * Daftar modul buat dropdown — diambil dari label menu yang sudah ada
     * (config/menus.php), supaya konsisten dengan modul yang benar-benar ada
     * di aplikasi, tidak perlu di-maintain di 2 tempat.
     */
    public static function moduleOptions(): array
    {
        $labels = [];
        foreach (config('menus') as $group) {
            foreach ($group['items'] ?? [] as $item) {
                if (($item['slug'] ?? null) === 'dashboard') continue;
                $labels[] = $item['label'];
            }
        }
        sort($labels);
        return array_values(array_unique($labels));
    }

    public function index()
    {
        return view('documentation.index', [
            'moduleOptions' => self::moduleOptions(),
        ]);
    }

    public function create()
    {
        return view('documentation.create', [
            'moduleOptions' => self::moduleOptions(),
        ]);
    }

    public function data(Request $request)
    {
        $query = DB::table('documentations as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.created_by')
            ->whereNull('d.deleted_at')
            ->select([
                'd.id_documentation',
                'd.judul',
                'd.modul',
                'd.is_published',
                'u.name as pembuat',
                'd.created_at',
            ]);

        if ($request->filled('modul')) {
            $query->where('d.modul', $request->modul);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('is_published', function ($row) {
                return $row->is_published
                    ? '<span class="badge rounded-pill" style="background:#dcfce7;color:#166534;font-size:11px;font-weight:600;">Published</span>'
                    : '<span class="badge rounded-pill" style="background:#f1f5f9;color:#64748b;font-size:11px;font-weight:600;">Draft</span>';
            })
            ->rawColumns(['is_published'])
            ->make(true);
    }

    private function tagsFor($id): array
    {
        return DB::table('documentation_tag_pivot as p')
            ->join('documentation_tags as t', 't.id_tag', '=', 'p.id_tag')
            ->where('p.id_documentation', $id)
            ->orderBy('t.nama')
            ->pluck('t.nama')
            ->toArray();
    }

    private function syncTags($id, array $tagNames): void
    {
        DB::table('documentation_tag_pivot')->where('id_documentation', $id)->delete();

        foreach (array_unique(array_filter(array_map('trim', $tagNames))) as $nama) {
            $idTag = DB::table('documentation_tags')->where('nama', $nama)->value('id_tag');
            if (!$idTag) {
                $idTag = DB::table('documentation_tags')->insertGetId([
                    'nama' => $nama,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('documentation_tag_pivot')->insert([
                'id_documentation' => $id,
                'id_tag' => $idTag,
            ]);
        }
    }

    private function uniqueSlug(string $judul, ?int $ignoreId = null): string
    {
        $base = Str::slug($judul);
        $slug = $base;
        $i = 1;
        while (
            DB::table('documentations')
                ->where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id_documentation', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'modul' => 'required|string|max:100',
            'konten' => 'required|string',
            'ringkasan' => 'nullable|string|max:255',
            'is_published' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
        ]);

        $id = DB::table('documentations')->insertGetId([
            'judul' => $validated['judul'],
            'slug' => $this->uniqueSlug($validated['judul']),
            'modul' => $validated['modul'],
            'konten' => $validated['konten'],
            'ringkasan' => $validated['ringkasan'] ?? null,
            'is_published' => $request->boolean('is_published', true),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->syncTags($id, $validated['tags'] ?? []);

        $after = DB::table('documentations')->where('id_documentation', $id)->get()->toJson();
        saveAudit('documentations', $id, 'Create', '', $after);

        return response()->json([
            'success' => true,
            'message' => 'Dokumentasi berhasil dibuat',
            'id' => $id,
        ]);
    }

    public function detail($id)
    {
        $data = DB::table('documentations as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.created_by')
            ->where('d.id_documentation', $id)
            ->whereNull('d.deleted_at')
            ->select(['d.*', 'u.name as pembuat'])
            ->first();

        if (!$data) {
            return response()->json(['message' => 'Dokumentasi tidak ditemukan'], 404);
        }

        $data->tags = $this->tagsFor($id);

        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'modul' => 'required|string|max:100',
            'konten' => 'required|string',
            'ringkasan' => 'nullable|string|max:255',
            'is_published' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
        ]);

        $before = DB::table('documentations')->where('id_documentation', $id)->get()->toJson();

        $judulLama = DB::table('documentations')->where('id_documentation', $id)->value('judul');
        $slug = $judulLama === $validated['judul']
            ? DB::table('documentations')->where('id_documentation', $id)->value('slug')
            : $this->uniqueSlug($validated['judul'], (int) $id);

        DB::table('documentations')->where('id_documentation', $id)->update([
            'judul' => $validated['judul'],
            'slug' => $slug,
            'modul' => $validated['modul'],
            'konten' => $validated['konten'],
            'ringkasan' => $validated['ringkasan'] ?? null,
            'is_published' => $request->boolean('is_published', true),
            'updated_at' => now(),
        ]);

        $this->syncTags($id, $validated['tags'] ?? []);

        $after = DB::table('documentations')->where('id_documentation', $id)->get()->toJson();
        saveAudit('documentations', $id, 'update', $before, $after);

        return response()->json(['success' => true, 'message' => 'Dokumentasi berhasil diperbarui']);
    }

    public function destroy($id)
    {
        $before = DB::table('documentations')->where('id_documentation', $id)->get()->toJson();
        DB::table('documentations')->where('id_documentation', $id)->update(['deleted_at' => now()]);
        $after = DB::table('documentations')->where('id_documentation', $id)->get()->toJson();
        saveAudit('documentations', $id, 'delete', $before, $after);

        return response()->json(['success' => true, 'message' => 'Dokumentasi berhasil dihapus']);
    }

    public function tagSelect2(Request $request)
    {
        $search = $request->q;

        $data = DB::table('documentation_tags')
            ->when($search, fn($q) => $q->where('nama', 'like', "%{$search}%"))
            ->orderBy('nama')
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($item) => ['id' => $item->nama, 'text' => $item->nama])
        );
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        $upload = uploadAttachment([$request->file('image')], 'documentations');
        $path = $upload['files'][0] ?? null;

        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Gagal upload gambar'], 500);
        }

        return response()->json([
            'success' => true,
            'url' => Storage::url($path),
        ]);
    }
}
