<?php

namespace App\Http\Controllers;

use App\Support\DataImport\ImportDefinition;
use App\Support\DataImport\ImportRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Menu "Import Data" — import Excel per jenis data (lihat App\Support\DataImport\ImportRegistry).
 *
 * Alur: pilih jenis → pilih Site → download template (identitas site tertanam di sheet "Info") →
 * upload → PRATINJAU (validasi tanpa menyimpan, file ditahan sementara) → PROSES (validasi ulang lalu simpan
 * dalam 1 transaction; baris valid masuk, duplikat/error dilewati dan dilaporkan di file hasil).
 * Controller ini hanya alur umum; aturan tiap jenis ada di kelas ImportDefinition-nya.
 */
class DataImportController extends Controller
{
    private const MAX_ROWS = 2000;
    private const SHEET_DATA = 'Data';
    private const SHEET_INFO = 'Info';
    /** Baris sheet Data: 1 = header, 2 = tipe data, 3 = contoh; data diimpor mulai baris ini. */
    private const FIRST_DATA_ROW = 4;

    // ── Halaman & data pendukung ─────────────────────────────────────────

    public function index()
    {
        $jenis = collect(ImportRegistry::all())->map(fn(ImportDefinition $d) => [
            'key' => $d->key(), 'label' => $d->label(), 'note' => $d->note(), 'contexts' => $d->contexts(),
        ])->values();

        return view('data-import.index', ['title' => 'Import Data', 'jenis' => $jenis]);
    }

    /** Daftar opsi untuk pilihan konteks jenis import (dimuat sekali, difilter di klien — pola Select2 proyek). */
    public function options(string $source)
    {
        $list = $this->optionList($source);
        if ($list === null) abort(404);
        return response()->json($list);
    }

    /** Sumber opsi yang boleh dipakai `ImportDefinition::contexts()[*]['source']`. Tambah `case` untuk sumber baru. */
    private function optionList(string $source): ?array
    {
        return match ($source) {
            'sites' => $this->siteOptions(),
            default => null,
        };
    }

    private function siteOptions(): array
    {
        $rows = DB::table('business_relation_sites as s')
            ->join('business_relations as br', 'br.id_br', '=', 's.id_br')
            ->leftJoin('entitas as e', 'e.id_entitas', '=', 'br.id_entitas')
            ->whereNull('s.deleted_at')
            ->orderBy('br.nama')->orderBy('s.nama_lokasi')
            ->get(['s.id_site', 's.nama_lokasi', 'br.nama as nama_br', 'e.nama as entitas']);

        return $rows->map(fn($r) => [
            'id'   => $r->id_site,
            'text' => brDisplayName($r->entitas, $r->nama_br) . ' — ' . $r->nama_lokasi,
        ])->values()->all();
    }

    // ── Template ─────────────────────────────────────────────────────────

    public function template(Request $request)
    {
        if ($deny = $this->guard()) return $deny;
        [$def, $ctx, $err] = $this->resolve($request);
        if ($err) return $err;

        $ss = new Spreadsheet();

        // Sheet Data: baris 1 header, baris 2 tipe data, baris 3 contoh (keduanya tidak diimpor); data mulai baris 4
        $sheet = $ss->getActiveSheet()->setTitle(self::SHEET_DATA);
        $cols = $def->columns();
        $contoh = $def->example();
        foreach ($cols as $i => $c) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue([$i + 1, 1], $c['label'] . ($c['required'] ? ' *' : ''));
            $sheet->setCellValueExplicit([$i + 1, 2], (string) $c['type'], DataType::TYPE_STRING);
            if (array_key_exists($i, $contoh) && $contoh[$i] !== null) {
                is_string($contoh[$i])
                    ? $sheet->setCellValueExplicit([$i + 1, 3], $contoh[$i], DataType::TYPE_STRING)
                    : $sheet->setCellValue([$i + 1, 3], $contoh[$i]);
            }
            $sheet->getColumnDimension($col)->setWidth($c['width'] ?? 18);

            $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($c['required'] ? '1E3A8A' : '64748B');
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            // Baris 2 (tipe) & 3 (contoh): abu-abu miring supaya jelas bukan data
            $sheet->getStyle($col . '2:' . $col . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $sheet->getStyle($col . '2:' . $col . '3')->getFont()->setItalic(true)->getColor()->setRGB('64748B');
        }
        $sheet->freezePane('A4');

        // Sheet Info: identitas file (jenis + nilai konteks), dicocokkan saat upload. Baris "info:*" hanya teks untuk manusia.
        $infoSheet = $ss->createSheet()->setTitle(self::SHEET_INFO);
        $infoSheet->setCellValue('A1', 'JANGAN DIUBAH — dipakai untuk mencocokkan file dengan pilihan di halaman Import Data');
        $infoSheet->setCellValue('A2', 'jenis');
        $infoSheet->setCellValueExplicit('B2', $def->key(), DataType::TYPE_STRING);
        $r = 3;
        foreach ($ctx['values'] as $k => $v) {
            $infoSheet->setCellValue([1, $r], $k);
            $infoSheet->setCellValueExplicit([2, $r], (string) $v, DataType::TYPE_STRING);
            $r++;
        }
        foreach ($ctx['info'] as $label => $teks) {
            $infoSheet->setCellValue([1, $r], 'info:' . $label);
            $infoSheet->setCellValueExplicit([2, $r], (string) $teks, DataType::TYPE_STRING);
            $r++;
        }
        $infoSheet->getStyle('A1')->getFont()->setBold(true)->getColor()->setRGB('B91C1C');
        $infoSheet->getColumnDimension('A')->setWidth(22);
        $infoSheet->getColumnDimension('B')->setWidth(48);

        // Sheet Petunjuk
        $pet = $ss->createSheet()->setTitle('Petunjuk');
        $pet->setCellValue('A1', 'Petunjuk Pengisian — ' . $def->label());
        $pet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $r = 3;
        foreach ($def->rules() as $rule) { $pet->setCellValue([1, $r++], '• ' . $rule); }
        $pet->getColumnDimension('A')->setWidth(110);

        $ss->setActiveSheetIndex(0);

        $dir = $this->dir('tmp');
        $path = $dir . '/template_' . auth()->id() . '_' . bin2hex(random_bytes(6)) . '.xlsx';
        (new Xlsx($ss))->save($path);

        $nama = 'template_' . $def->key() . ($ctx['values'] ? '_' . implode('_', $ctx['values']) : '') . '.xlsx';
        return response()->download($path, $nama)->deleteFileAfterSend(true);
    }

    // ── Pratinjau ────────────────────────────────────────────────────────

    public function preview(Request $request)
    {
        if ($deny = $this->guard()) return $deny;
        $request->validate(['file' => 'required|file|mimes:xlsx|max:5120'], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes'    => 'File harus berformat .xlsx.',
            'file.max'      => 'Ukuran file maksimal 5 MB.',
        ]);
        [$def, $ctx, $err] = $this->resolve($request);
        if ($err) return $err;

        $this->cleanup();

        $token = bin2hex(random_bytes(16));
        $path = $this->tmpPath($token);
        $request->file('file')->move(dirname($path), basename($path));

        $parsed = $this->parse($path, $def, $ctx['values']);
        if (isset($parsed['error'])) {
            @unlink($path);
            return response()->json(['success' => false, 'message' => $parsed['error']], 422);
        }

        $state = $def->prepare($ctx['values']);
        $count = ['valid' => 0, 'skip' => 0, 'error' => 0];
        $rows = [];
        foreach ($parsed['rows'] as $row) {
            $state['_baris'] = $row['no'];
            $res = $def->validate($row['raw'], $state);
            $count[$res['status']]++;
            if (count($rows) < 300) {
                [$a, $b] = $def->summary($row['raw']);
                $rows[] = ['no' => $row['no'], 'a' => $a, 'b' => $b, 'status' => $res['status'], 'message' => $res['message']];
            }
        }

        return response()->json([
            'success' => true,
            'token'   => $token,
            'total'   => count($parsed['rows']),
            'count'   => $count,
            'rows'    => $rows,
            'labels'  => $def->summaryLabels(),
            'dipotong' => count($parsed['rows']) > count($rows),
        ]);
    }

    // ── Proses ───────────────────────────────────────────────────────────

    public function process(Request $request)
    {
        if ($deny = $this->guard()) return $deny;
        $request->validate(['token' => ['required', 'regex:/^[a-f0-9]{32}$/']]);
        [$def, $ctx, $err] = $this->resolve($request);
        if ($err) return $err;

        $path = $this->tmpPath($request->token);
        if (!is_file($path)) {
            return response()->json(['success' => false, 'message' => 'Sesi pratinjau sudah kedaluwarsa. Upload ulang file Anda.'], 422);
        }

        // File dibaca & divalidasi ulang — data bisa berubah sejak pratinjau
        $parsed = $this->parse($path, $def, $ctx['values']);
        if (isset($parsed['error'])) {
            return response()->json(['success' => false, 'message' => $parsed['error']], 422);
        }

        $state = $def->prepare($ctx['values']);
        $status = [];   // no baris => [kelas, teks]
        $summary = ['inserted' => 0, 'skipped' => 0, 'errors' => 0];
        $catatan = [];  // daftar yang dilewati / error untuk ditampilkan di akhir

        DB::transaction(function () use ($parsed, $def, $ctx, &$state, &$status, &$summary, &$catatan) {
            foreach ($parsed['rows'] as $row) {
                $state['_baris'] = $row['no'];
                $res = $def->validate($row['raw'], $state);
                [$a] = $def->summary($row['raw']);

                if ($res['status'] === 'valid') {
                    $def->insert($res['clean'], $ctx['values']);
                    $summary['inserted']++;
                    $status[$row['no']] = ['ok', 'Berhasil ditambahkan'];
                } elseif ($res['status'] === 'skip') {
                    $summary['skipped']++;
                    $status[$row['no']] = ['skip', 'Dilewati: ' . $res['message']];
                    $catatan[] = "Baris {$row['no']} ({$a}): dilewati — {$res['message']}";
                } else {
                    $summary['errors']++;
                    $status[$row['no']] = ['error', 'Error: ' . $res['message']];
                    $catatan[] = "Baris {$row['no']} ({$a}): error — {$res['message']}";
                }
            }
        });

        $resultFile = $this->buildResultFile($path, $def, $status);
        @unlink($path);

        return response()->json([
            'success'     => true,
            'summary'     => $summary,
            'catatan'     => array_slice($catatan, 0, 200),
            'catatan_total' => count($catatan),
            'result_file' => $resultFile,
        ]);
    }

    public function downloadResult(string $file)
    {
        // Nama file dibuat server: "{userId}_{acak}.xlsx" — hanya milik user yang login
        if (!preg_match('/^' . auth()->id() . '_[a-f0-9]{16}\.xlsx$/', $file)) abort(404);
        $path = $this->dir('hasil') . '/' . $file;
        if (!is_file($path)) abort(404, 'File hasil tidak ditemukan atau sudah dihapus.');

        return response()->download($path, 'hasil_import_' . date('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    // ── Pembantu ─────────────────────────────────────────────────────────

    /** Import menulis data BR — wajib boleh membuat data Business Relation (selain izin menu Import Data). */
    private function guard()
    {
        if (!userCan('business-relations', 'can_create')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses membuat data Business Relation.'], 403);
        }
        return null;
    }

    /** [definisi, konteks ['values','info'], errorResponse] dari `jenis` + `ctx[...]` di request. */
    private function resolve(Request $request): array
    {
        $def = ImportRegistry::get($request->input('jenis'));
        if (!$def) return [null, null, response()->json(['success' => false, 'message' => 'Jenis import tidak dikenal.'], 422)];

        $ctx = $def->resolveContext((array) $request->input('ctx', []));
        if (is_string($ctx)) {
            return [null, null, response()->json(['success' => false, 'message' => $ctx], 422)];
        }
        return [$def, $ctx, null];
    }

    private function dir(string $sub): string
    {
        $dir = storage_path('app/data-import/' . $sub);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        return $dir;
    }

    private function tmpPath(string $token): string
    {
        return $this->dir('tmp') . '/' . auth()->id() . '_' . $token . '.xlsx';
    }

    /** File sementara & hasil yang tidak diambil dibersihkan setelah 24 jam. */
    private function cleanup(): void
    {
        foreach (['tmp', 'hasil'] as $sub) {
            foreach (glob($this->dir($sub) . '/*.xlsx') ?: [] as $f) {
                if (filemtime($f) < time() - 86400) @unlink($f);
            }
        }
    }

    /**
     * Baca file upload: cek sheet Info cocok dengan jenis & site, petakan kolom dari header, ambil baris tidak kosong.
     * @return array ['error'=>string] atau ['rows'=>[['no'=>excelRow,'raw'=>[key=>nilai]], ...], 'headerCols'=>int]
     */
    private function parse(string $path, ImportDefinition $def, array $context): array
    {
        try {
            $ss = IOFactory::load($path);
        } catch (\Throwable $e) {
            return ['error' => 'File tidak bisa dibaca. Pastikan file Excel (.xlsx) hasil unduhan template.'];
        }

        $info = $ss->getSheetByName(self::SHEET_INFO);
        $data = $ss->getSheetByName(self::SHEET_DATA);
        if (!$info || !$data) {
            return ['error' => 'Template tidak dikenali. Gunakan file hasil "Download Template" (sheet "Data" dan "Info" harus ada).'];
        }
        if (trim((string) $info->getCell('B2')->getValue()) !== $def->key()) {
            return ['error' => 'File ini template untuk jenis data lain. Download ulang template untuk "' . $def->label() . '".'];
        }
        // Nilai konteks di sheet Info harus sama dengan pilihan di halaman (mis. id_site), kalau tidak file milik data lain
        $fileInfo = [];
        foreach ($info->toArray(null, true, false, false) as $r) {
            if (isset($r[0]) && $r[0] !== null && $r[0] !== '') $fileInfo[trim((string) $r[0])] = trim((string) ($r[1] ?? ''));
        }
        foreach ($context as $k => $v) {
            if (($fileInfo[$k] ?? null) !== (string) $v) {
                $untuk = collect($fileInfo)->filter(fn($x, $key) => str_starts_with($key, 'info:'))->implode(' / ');
                return ['error' => 'File ini template untuk ' . ($untuk !== '' ? '"' . $untuk . '"' : 'pilihan lain') . ', bukan pilihan Anda sekarang. Download template ulang dari halaman ini.'];
            }
        }

        $grid = $data->toArray(null, true, false, false);
        $header = $grid[0] ?? [];
        $norm = fn($s) => mb_strtolower(trim(rtrim(trim((string) $s), '*')));
        $peta = [];
        foreach ($def->columns() as $c) {
            $idx = null;
            foreach ($header as $i => $h) {
                if ($norm($h) === mb_strtolower($c['label'])) { $idx = $i; break; }
            }
            if ($idx === null && $c['required']) {
                return ['error' => 'Kolom "' . $c['label'] . '" tidak ditemukan di sheet Data. Jangan mengubah baris header template.'];
            }
            $peta[$c['key']] = $idx;
        }

        // Baris 2 (tipe data) harus masih utuh — kalau dihapus, data di baris 2-3 akan terlewat diam-diam
        $kolom1 = $def->columns()[0];
        $idx1 = $peta[$kolom1['key']];
        if (trim((string) ($grid[1][$idx1] ?? '')) !== $kolom1['type']) {
            return ['error' => 'Baris 2 (tipe data) dan baris 3 (contoh) tidak boleh dihapus atau diubah. Isi data mulai dari baris ' . self::FIRST_DATA_ROW . ', atau download template baru.'];
        }

        $rows = [];
        foreach (array_slice($grid, self::FIRST_DATA_ROW - 1, null, true) as $i => $cells) {
            $raw = [];
            $kosong = true;
            foreach ($peta as $key => $idx) {
                $v = $idx === null ? null : ($cells[$idx] ?? null);
                if (is_string($v)) $v = trim($v);
                if ($v !== null && $v !== '') $kosong = false;
                $raw[$key] = $v;
            }
            if ($kosong) continue;
            $rows[] = ['no' => $i + 1, 'raw' => $raw];
            if (count($rows) > self::MAX_ROWS) {
                return ['error' => 'Maksimal ' . self::MAX_ROWS . ' baris per file. Pecah file menjadi beberapa bagian.'];
            }
        }
        if (!$rows) return ['error' => 'Tidak ada baris data di sheet "Data". Isi data mulai dari baris ' . self::FIRST_DATA_ROW . '.'];

        return ['rows' => $rows, 'headerCols' => count($header)];
    }

    /** Salin file upload + kolom "Status Import" berwarna per baris, simpan sebagai file hasil. */
    private function buildResultFile(string $path, ImportDefinition $def, array $status): ?string
    {
        try {
            $ss = IOFactory::load($path);
            $sheet = $ss->getSheetByName(self::SHEET_DATA);
            $col = count($def->columns()) + 1;
            $letter = Coordinate::stringFromColumnIndex($col);

            $sheet->setCellValue([$col, 1], 'Status Import');
            $sheet->getStyle($letter . '1')->getFont()->setBold(true);
            $sheet->getColumnDimension($letter)->setWidth(46);

            $warna = ['ok' => 'DCFCE7', 'skip' => 'FEF3C7', 'error' => 'FEE2E2'];
            foreach ($status as $no => [$kelas, $teks]) {
                $sheet->setCellValue([$col, $no], $teks);
                $sheet->getStyle($letter . $no)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna[$kelas]);
            }

            $file = auth()->id() . '_' . bin2hex(random_bytes(8)) . '.xlsx';
            (new Xlsx($ss))->save($this->dir('hasil') . '/' . $file);
            return $file;
        } catch (\Throwable $e) {
            report($e);
            return null; // import tetap berhasil; hanya file hasilnya yang gagal dibuat
        }
    }
}
