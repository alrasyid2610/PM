<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Import data master Lab Testing dari Excel — PER MODUL (bukan gabungan
 * banyak tabel sekaligus), supaya risikonya kecil: user pilih 1 modul,
 * download template modul itu saja, isi, lalu upload & eksekusi modul itu
 * saja. Modul yang punya relasi ke modul lain (Matriks Sample, Testing
 * Point, Testing Item) tetap merujuk lewat kode/nomor teks, di-resolve ke
 * ID saat import — jadi modul induknya harus sudah ada datanya duluan
 * (diimport terpisah, sebelum modul anaknya).
 */
class LabDataImportController extends Controller
{
    private function modules(): array
    {
        return [
            'testing_units' => [
                'label'   => 'Testing Units',
                'headers' => ['Kode', 'Judul Indonesia', 'Judul Inggris', 'Keterangan'],
                'example' => fn() => DB::table('testing_units')->whereNull('deleted_at')->orderBy('id_testing_unit')->limit(3)->get()
                    ->map(fn($r) => [$r->kode, $r->judul_indonesia, $r->judul_inggris, $r->keterangan])->toArray(),
                'importer' => 'importUnits',
            ],
            'testing_parameters' => [
                'label'   => 'Testing Parameters',
                'headers' => ['Kelompok', 'Kode', 'Judul Indonesia', 'Judul Inggris', 'Rumus Empiris', 'Judul IUPAC', 'Referensi', 'Keterangan'],
                'example' => fn() => DB::table('testing_parameters')->whereNull('deleted_at')->orderBy('id_testing_parameter')->limit(3)->get()
                    ->map(fn($r) => [$r->kelompok, $r->kode, $r->judul_indonesia, $r->judul_inggris, $r->rumus_empiris, $r->judul_iupac, $r->referensi, $r->keterangan])->toArray(),
                'importer' => 'importParameters',
            ],
            'testing_kelompok_matriks_samples' => [
                'label'   => 'Kelompok Matriks Sample',
                'headers' => ['Kode', 'Judul Indonesia', 'Judul Inggris', 'Keterangan'],
                'example' => fn() => DB::table('testing_kelompok_matriks_samples')->whereNull('deleted_at')->orderBy('id_testing_kelompok_matriks_sample')->limit(3)->get()
                    ->map(fn($r) => [$r->kode, $r->judul_indonesia, $r->judul_inggris, $r->keterangan])->toArray(),
                'importer' => 'importKelompok',
            ],
            'testing_matriks_samples' => [
                'label'   => 'Matriks Sample',
                'headers' => ['Kode Kelompok', 'Kode', 'Judul Indonesia', 'Judul Inggris', 'Keterangan'],
                'note'    => 'Butuh data Kelompok Matriks Sample sudah ada (kolom "Kode Kelompok" merujuk ke situ).',
                'example' => fn() => DB::table('testing_matriks_samples as m')
                    ->join('testing_kelompok_matriks_samples as k', 'k.id_testing_kelompok_matriks_sample', '=', 'm.id_testing_kelompok_matriks_sample')
                    ->whereNull('m.deleted_at')->orderBy('m.id_testing_matriks_sample')->limit(3)
                    ->get(['k.kode as kode_kelompok', 'm.kode', 'm.judul_indonesia', 'm.judul_inggris', 'm.keterangan'])
                    ->map(fn($r) => [$r->kode_kelompok, $r->kode, $r->judul_indonesia, $r->judul_inggris, $r->keterangan])->toArray(),
                'importer' => 'importMatriks',
            ],
            'testing_standards' => [
                'label'   => 'Testing Standards',
                'headers' => ['Nomor', 'Judul', 'Status Aktif (1/0)'],
                'example' => fn() => DB::table('testing_standards')->whereNull('deleted_at')->orderBy('id_testing_standard')->limit(3)->get()
                    ->map(fn($r) => [$r->nomor, $r->judul, $r->is_aktif])->toArray(),
                'importer' => 'importStandards',
            ],
            'testing_points' => [
                'label'   => 'Testing Points',
                'headers' => ['Nomor Standard', 'Kode Matriks Sample', 'Nama', 'Deskripsi', 'Nomor Halaman', 'Keterangan', 'Status Aktif (1/0)'],
                'note'    => 'Butuh data Testing Standards & Matriks Sample sudah ada.',
                'example' => fn() => DB::table('testing_points as p')
                    ->join('testing_standards as s', 's.id_testing_standard', '=', 'p.id_testing_standard')
                    ->join('testing_matriks_samples as m', 'm.id_testing_matriks_sample', '=', 'p.id_testing_matriks_sample')
                    ->whereNull('p.deleted_at')->orderBy('p.id_testing_point')->limit(3)
                    ->get(['s.nomor as nomor_standard', 'm.kode as kode_matriks', 'p.nama', 'p.deskripsi', 'p.nomor_halaman', 'p.keterangan', 'p.is_aktif'])
                    ->map(fn($r) => [$r->nomor_standard, $r->kode_matriks, $r->nama, $r->deskripsi, $r->nomor_halaman, $r->keterangan, $r->is_aktif])->toArray(),
                'importer' => 'importPoints',
            ],
            'testing_items' => [
                'label'   => 'Testing Items',
                'headers' => ['Nomor Standard', 'Kode Matriks Sample', 'Nama Point', 'Kode Parameter', 'Kode Unit', 'Nilai', 'Nomor', 'Judul Indonesia', 'Judul Inggris', 'Keterangan', 'Status Aktif (1/0)'],
                'note'    => 'Butuh data Testing Points, Testing Parameters & Testing Units sudah ada.',
                'example' => fn() => DB::table('testing_items as i')
                    ->join('testing_points as p', 'p.id_testing_point', '=', 'i.id_testing_point')
                    ->join('testing_standards as s', 's.id_testing_standard', '=', 'p.id_testing_standard')
                    ->join('testing_matriks_samples as m', 'm.id_testing_matriks_sample', '=', 'p.id_testing_matriks_sample')
                    ->join('testing_parameters as prm', 'prm.id_testing_parameter', '=', 'i.id_testing_parameter')
                    ->join('testing_units as u', 'u.id_testing_unit', '=', 'i.id_testing_unit')
                    ->orderBy('i.id_testing_item')->limit(3)
                    ->get(['s.nomor as nomor_standard', 'm.kode as kode_matriks', 'p.nama as nama_point', 'prm.kode as kode_parameter', 'u.kode as kode_unit', 'i.nilai', 'i.nomor', 'i.judul_indonesia', 'i.judul_inggris', 'i.keterangan', 'i.is_aktif'])
                    ->map(fn($r) => [$r->nomor_standard, $r->kode_matriks, $r->nama_point, $r->kode_parameter, $r->kode_unit, $r->nilai, $r->nomor, $r->judul_indonesia, $r->judul_inggris, $r->keterangan, $r->is_aktif])->toArray(),
                'importer' => 'importItems',
            ],
        ];
    }

    public function index()
    {
        $modules = collect($this->modules())->map(fn($m, $key) => [
            'key' => $key, 'label' => $m['label'], 'note' => $m['note'] ?? null,
        ])->values();

        return view('lab-data-import.index', ['title' => 'Import Data Lab', 'modules' => $modules]);
    }

    public function downloadTemplate(Request $request)
    {
        $request->validate(['modul' => 'required|string']);
        $modules = $this->modules();
        if (!isset($modules[$request->modul])) {
            abort(404, 'Modul tidak dikenal');
        }
        $config = $modules[$request->modul];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($config['label'], 0, 31));
        $this->fillSheet($sheet, $config['headers'], ($config['example'])());

        $fileName = 'template_' . $request->modul . '_' . date('Ymd_His') . '.xlsx';
        $tmpPath  = storage_path('app/' . $fileName);
        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }

    private function fillSheet($sheet, array $headers, array $exampleRows): void
    {
        foreach ($headers as $i => $header) {
            $sheet->getCellByColumnAndRow($i + 1, 1)->setValue($header);
        }
        $headerStyle = $sheet->getStyleByColumnAndRow(1, 1, count($headers), 1);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFont()->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A3A6E');

        $rowNum = 2;
        foreach ($exampleRows as $row) {
            foreach ($row as $i => $value) {
                $sheet->getCellByColumnAndRow($i + 1, $rowNum)->setValue($value);
            }
            $rowNum++;
        }

        foreach (range(1, count($headers)) as $colIdx) {
            $sheet->getColumnDimensionByColumn($colIdx)->setWidth(22);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'modul' => 'required|string',
            'file'  => 'required|file|mimes:xlsx,xls',
        ]);

        $modules = $this->modules();
        if (!isset($modules[$request->modul])) {
            return response()->json(['success' => false, 'message' => 'Modul tidak dikenal'], 422);
        }
        $importer = $modules[$request->modul]['importer'];

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        DB::beginTransaction();
        try {
            $result = $this->{$importer}($rows);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import gagal: ' . $e->getMessage()], 500);
        }

        $resultFile = $this->buildResultFile($request->modul, $modules[$request->modul]['headers'], $result['rows']);

        unset($result['rows']);
        return response()->json(['success' => true, 'result' => $result, 'result_file' => $resultFile]);
    }

    // ── Buat file hasil import (data asli + kolom Status) untuk didownload ─────
    private function buildResultFile(string $modul, array $headers, array $rows): ?string
    {
        if (empty($rows)) return null;

        $this->cleanupOldResultFiles();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $this->fillSheet($sheet, [...$headers, 'Status'], $rows);

        // Warnai kolom Status: hijau utk "Berhasil...", kuning utk "Dilewati..."
        $statusCol = count($headers) + 1;
        foreach ($rows as $i => $row) {
            $status = end($row);
            $isSuccess = str_starts_with((string) $status, 'Berhasil');
            $sheet->getStyleByColumnAndRow($statusCol, $i + 2, $statusCol, $i + 2)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($isSuccess ? 'DCFCE7' : 'FEF3C7');
        }

        $dir = storage_path('app/lab-import-results');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $fileName = 'hasil_import_' . $modul . '_' . date('Ymd_His') . '_' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($dir . '/' . $fileName);

        return $fileName;
    }

    private function cleanupOldResultFiles(): void
    {
        $dir = storage_path('app/lab-import-results');
        if (!is_dir($dir)) return;
        foreach (glob($dir . '/*.xlsx') ?: [] as $file) {
            if (filemtime($file) < time() - 86400) @unlink($file);
        }
    }

    public function downloadResult(string $file)
    {
        if (!preg_match('/^[a-zA-Z0-9_\.]+\.xlsx$/', $file)) {
            abort(404);
        }
        $path = storage_path('app/lab-import-results/' . $file);
        if (!is_file($path)) {
            abort(404, 'File hasil import sudah tidak tersedia (kadaluarsa setelah 24 jam).');
        }
        return response()->download($path, $file)->deleteFileAfterSend(true);
    }

    // ── Helper: buang baris header, mulai dari baris ke-2 ───────────────────────
    private function dataRows(array $rows): array
    {
        return array_slice($rows, 1);
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn($v) => $v !== null && $v !== '')->isEmpty();
    }

    private function result(): array
    {
        return ['inserted' => 0, 'skipped' => 0, 'errors' => [], 'rows' => []];
    }

    private function markRow(array &$res, array $row, string $status): void
    {
        $res['rows'][] = [...$row, $status];
    }

    private function importUnits(array $rows): array
    {
        $res = $this->result();
        foreach ($this->dataRows($rows) as $i => $row) {
            if ($this->isEmptyRow($row)) continue;
            [$kode, $indo, $inggris, $ket] = array_pad($row, 4, null);
            if (!$kode) {
                $res['skipped']++; $res['errors'][] = "Baris " . ($i + 2) . ": kode kosong";
                $this->markRow($res, $row, 'Dilewati: kode kosong');
                continue;
            }
            if (DB::table('testing_units')->where('kode', $kode)->whereNull('deleted_at')->exists()) {
                $res['skipped']++; $res['errors'][] = "Baris " . ($i + 2) . ": kode \"$kode\" sudah ada, dilewati";
                $this->markRow($res, $row, 'Dilewati: kode sudah ada');
                continue;
            }
            DB::table('testing_units')->insert([
                'kode' => $kode, 'judul_indonesia' => $indo, 'judul_inggris' => $inggris, 'keterangan' => $ket,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $res['inserted']++;
            $this->markRow($res, $row, 'Berhasil ditambahkan');
        }
        return $res;
    }

    private function importParameters(array $rows): array
    {
        $res = $this->result();
        foreach ($this->dataRows($rows) as $i => $row) {
            if ($this->isEmptyRow($row)) continue;
            [$kelompok, $kode, $indo, $inggris, $rumus, $iupac, $referensi, $ket] = array_pad($row, 8, null);
            if (!$kode) {
                $res['skipped']++; $res['errors'][] = "Baris " . ($i + 2) . ": kode kosong";
                $this->markRow($res, $row, 'Dilewati: kode kosong');
                continue;
            }
            if (DB::table('testing_parameters')->where('kode', $kode)->whereNull('deleted_at')->exists()) {
                $res['skipped']++; $res['errors'][] = "Baris " . ($i + 2) . ": kode \"$kode\" sudah ada, dilewati";
                $this->markRow($res, $row, 'Dilewati: kode sudah ada');
                continue;
            }
            DB::table('testing_parameters')->insert([
                'kelompok' => $kelompok, 'kode' => $kode, 'judul_indonesia' => $indo, 'judul_inggris' => $inggris,
                'rumus_empiris' => $rumus, 'judul_iupac' => $iupac, 'referensi' => $referensi, 'keterangan' => $ket,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $res['inserted']++;
            $this->markRow($res, $row, 'Berhasil ditambahkan');
        }
        return $res;
    }

    private function importKelompok(array $rows): array
    {
        $res = $this->result();
        foreach ($this->dataRows($rows) as $i => $row) {
            if ($this->isEmptyRow($row)) continue;
            [$kode, $indo, $inggris, $ket] = array_pad($row, 4, null);
            if (!$kode) {
                $res['skipped']++; $res['errors'][] = "Baris " . ($i + 2) . ": kode kosong";
                $this->markRow($res, $row, 'Dilewati: kode kosong');
                continue;
            }
            if (DB::table('testing_kelompok_matriks_samples')->where('kode', $kode)->whereNull('deleted_at')->exists()) {
                $res['skipped']++; $res['errors'][] = "Baris " . ($i + 2) . ": kode \"$kode\" sudah ada, dilewati";
                $this->markRow($res, $row, 'Dilewati: kode sudah ada');
                continue;
            }
            DB::table('testing_kelompok_matriks_samples')->insert([
                'kode' => $kode, 'judul_indonesia' => $indo, 'judul_inggris' => $inggris, 'keterangan' => $ket,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $res['inserted']++;
            $this->markRow($res, $row, 'Berhasil ditambahkan');
        }
        return $res;
    }

    private function importMatriks(array $rows): array
    {
        $res = $this->result();
        foreach ($this->dataRows($rows) as $i => $row) {
            if ($this->isEmptyRow($row)) continue;
            [$kodeKelompok, $kode, $indo, $inggris, $ket] = array_pad($row, 5, null);
            $line = $i + 2;
            if (!$kode) {
                $res['skipped']++; $res['errors'][] = "Baris $line: kode kosong";
                $this->markRow($res, $row, 'Dilewati: kode kosong');
                continue;
            }
            if (DB::table('testing_matriks_samples')->where('kode', $kode)->whereNull('deleted_at')->exists()) {
                $res['skipped']++; $res['errors'][] = "Baris $line: kode \"$kode\" sudah ada, dilewati";
                $this->markRow($res, $row, 'Dilewati: kode sudah ada');
                continue;
            }
            $idKelompok = DB::table('testing_kelompok_matriks_samples')->where('kode', $kodeKelompok)->whereNull('deleted_at')->value('id_testing_kelompok_matriks_sample');
            if (!$idKelompok) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Kode Kelompok \"$kodeKelompok\" tidak ditemukan";
                $this->markRow($res, $row, 'Dilewati: Kode Kelompok tidak ditemukan');
                continue;
            }
            DB::table('testing_matriks_samples')->insert([
                'id_testing_kelompok_matriks_sample' => $idKelompok,
                'kode' => $kode, 'judul_indonesia' => $indo, 'judul_inggris' => $inggris, 'keterangan' => $ket,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $res['inserted']++;
            $this->markRow($res, $row, 'Berhasil ditambahkan');
        }
        return $res;
    }

    private function importStandards(array $rows): array
    {
        $res = $this->result();
        foreach ($this->dataRows($rows) as $i => $row) {
            if ($this->isEmptyRow($row)) continue;
            [$nomor, $judul, $aktif] = array_pad($row, 3, null);
            $line = $i + 2;
            if (!$nomor) {
                $res['skipped']++; $res['errors'][] = "Baris $line: nomor kosong";
                $this->markRow($res, $row, 'Dilewati: nomor kosong');
                continue;
            }
            if (DB::table('testing_standards')->where('nomor', $nomor)->whereNull('deleted_at')->exists()) {
                $res['skipped']++; $res['errors'][] = "Baris $line: nomor \"$nomor\" sudah ada, dilewati";
                $this->markRow($res, $row, 'Dilewati: nomor sudah ada');
                continue;
            }
            DB::table('testing_standards')->insert([
                'nomor' => $nomor, 'judul' => $judul, 'is_aktif' => $aktif === null ? 1 : (int) $aktif,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $res['inserted']++;
            $this->markRow($res, $row, 'Berhasil ditambahkan');
        }
        return $res;
    }

    private function importPoints(array $rows): array
    {
        $res = $this->result();
        foreach ($this->dataRows($rows) as $i => $row) {
            if ($this->isEmptyRow($row)) continue;
            [$nomorStandard, $kodeMatriks, $nama, $deskripsi, $nomorHalaman, $ket, $aktif] = array_pad($row, 7, null);
            $line = $i + 2;
            if (!$nama) {
                $res['skipped']++; $res['errors'][] = "Baris $line: nama kosong";
                $this->markRow($res, $row, 'Dilewati: nama kosong');
                continue;
            }

            $idStandard = DB::table('testing_standards')->where('nomor', $nomorStandard)->whereNull('deleted_at')->value('id_testing_standard');
            if (!$idStandard) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Nomor Standard \"$nomorStandard\" tidak ditemukan";
                $this->markRow($res, $row, 'Dilewati: Nomor Standard tidak ditemukan');
                continue;
            }
            $idMatriks = DB::table('testing_matriks_samples')->where('kode', $kodeMatriks)->whereNull('deleted_at')->value('id_testing_matriks_sample');
            if (!$idMatriks) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Kode Matriks Sample \"$kodeMatriks\" tidak ditemukan";
                $this->markRow($res, $row, 'Dilewati: Kode Matriks Sample tidak ditemukan');
                continue;
            }
            $exists = DB::table('testing_points')
                ->where('id_testing_standard', $idStandard)
                ->where('id_testing_matriks_sample', $idMatriks)
                ->where('nama', $nama)
                ->whereNull('deleted_at')->exists();
            if ($exists) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Point \"$nama\" untuk Standard+Matriks ini sudah ada, dilewati";
                $this->markRow($res, $row, 'Dilewati: Point ini sudah ada');
                continue;
            }

            DB::table('testing_points')->insert([
                'id_testing_standard' => $idStandard, 'id_testing_matriks_sample' => $idMatriks,
                'nama' => $nama, 'deskripsi' => $deskripsi, 'nomor_halaman' => $nomorHalaman,
                'keterangan' => $ket, 'is_aktif' => $aktif === null ? 1 : (int) $aktif,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $res['inserted']++;
            $this->markRow($res, $row, 'Berhasil ditambahkan');
        }
        return $res;
    }

    private function importItems(array $rows): array
    {
        $res = $this->result();
        foreach ($this->dataRows($rows) as $i => $row) {
            if ($this->isEmptyRow($row)) continue;
            [$nomorStandard, $kodeMatriks, $namaPoint, $kodeParameter, $kodeUnit, $nilai, $nomor, $indo, $inggris, $ket, $aktif] = array_pad($row, 11, null);
            $line = $i + 2;
            if (!$indo) {
                $res['skipped']++; $res['errors'][] = "Baris $line: judul indonesia kosong";
                $this->markRow($res, $row, 'Dilewati: judul indonesia kosong');
                continue;
            }

            $idStandard = DB::table('testing_standards')->where('nomor', $nomorStandard)->whereNull('deleted_at')->value('id_testing_standard');
            $idMatriks  = DB::table('testing_matriks_samples')->where('kode', $kodeMatriks)->whereNull('deleted_at')->value('id_testing_matriks_sample');
            $idPoint    = ($idStandard && $idMatriks)
                ? DB::table('testing_points')->where('id_testing_standard', $idStandard)->where('id_testing_matriks_sample', $idMatriks)->where('nama', $namaPoint)->whereNull('deleted_at')->value('id_testing_point')
                : null;
            if (!$idPoint) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Testing Point untuk Standard \"$nomorStandard\" + Matriks \"$kodeMatriks\" + Nama \"$namaPoint\" tidak ditemukan";
                $this->markRow($res, $row, 'Dilewati: Testing Point tidak ditemukan');
                continue;
            }
            $idParameter = DB::table('testing_parameters')->where('kode', $kodeParameter)->whereNull('deleted_at')->value('id_testing_parameter');
            if (!$idParameter) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Kode Parameter \"$kodeParameter\" tidak ditemukan";
                $this->markRow($res, $row, 'Dilewati: Kode Parameter tidak ditemukan');
                continue;
            }
            $idUnit = DB::table('testing_units')->where('kode', $kodeUnit)->whereNull('deleted_at')->value('id_testing_unit');
            if (!$idUnit) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Kode Unit \"$kodeUnit\" tidak ditemukan";
                $this->markRow($res, $row, 'Dilewati: Kode Unit tidak ditemukan');
                continue;
            }
            $exists = DB::table('testing_items')
                ->where('id_testing_point', $idPoint)
                ->where('id_testing_parameter', $idParameter)
                ->where('id_testing_unit', $idUnit)
                ->exists();
            if ($exists) {
                $res['skipped']++; $res['errors'][] = "Baris $line: Item dengan Point+Parameter+Unit ini sudah ada, dilewati";
                $this->markRow($res, $row, 'Dilewati: Item ini sudah ada');
                continue;
            }

            DB::table('testing_items')->insert([
                'id_testing_point' => $idPoint, 'id_testing_parameter' => $idParameter, 'id_testing_unit' => $idUnit,
                'nilai' => $nilai, 'nomor' => $nomor, 'judul_indonesia' => $indo, 'judul_inggris' => $inggris,
                'keterangan' => $ket, 'is_aktif' => $aktif === null ? 1 : (int) $aktif,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $res['inserted']++;
            $this->markRow($res, $row, 'Berhasil ditambahkan');
        }
        return $res;
    }
}
