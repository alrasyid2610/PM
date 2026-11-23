<?php

namespace App\Http\Controllers;

use App\Support\TestingPointApproval;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Report "Export Data Points" (sejak 2026-11-23) — grup sidebar Report, read-only, tanpa tabel/preview
 * di layar (keputusan user): 1 tombol yang langsung mengunduh 1 file Excel (1 sheet flat) berisi
 * seluruh data Kelompok Matriks Sample, Matriks Sample, Testing Standard, Testing Points, dan Testing
 * Item — 1 baris = 1 Testing Item, kolom data induknya (Kelompok/Matriks/Standard/Testing Point) ikut
 * digabung/berulang di tiap baris.
 *
 * Cakupan: hanya yang AKTIF (`is_aktif` di tabel yang punya kolom itu) & belum dihapus (`deleted_at`
 * di tabel yang punya soft delete) di SELURUH rantai (Kelompok → Matriks → Standard → Testing Point →
 * Testing Item) — keputusan user. Testing Point ikut SEMUA status approval (Draft/Menunggu
 * Approval/Disetujui, kolom "Status Approval") — laporan ini untuk referensi data master lengkap,
 * BUKAN syarat pemakaian BOQ (beda dari select2/guard BOQ yang hanya memuat Disetujui).
 */
class ExportDataPointsController extends Controller
{
    private const HEADER = [
        'Kode Kelompok', 'Kelompok (ID)', 'Kelompok (EN)', 'Keterangan Kelompok',
        'Kode Matriks', 'Matriks (ID)', 'Matriks (EN)', 'Keterangan Matriks',
        'Nomor Standard', 'Judul Standard',
        'Nama Testing Point', 'Deskripsi Testing Point', 'Nomor Halaman', 'Keterangan Testing Point', 'Status Approval',
        'Nomor Item', 'Judul Item (ID)', 'Judul Item (EN)',
        'Kode Parameter', 'Parameter (ID)', 'Kode Satuan', 'Satuan (ID)', 'Nilai', 'Keterangan Item',
    ];

    public function index()
    {
        return view('export-data-points.index', ['total' => $this->query()->count()]);
    }

    public function export()
    {
        $rows = $this->query()->orderBy('tk.kode')->orderBy('tm.kode')->orderBy('ts.nomor')
            ->orderBy('tp.nama')->orderBy('ti.nomor')
            ->get();

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet()->setTitle('Data Points');

        foreach (self::HEADER as $i => $label) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValueExplicit([$i + 1, 1], $label, DataType::TYPE_STRING);
            $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setWidth(22);
        }

        $r = 2;
        foreach ($rows as $row) {
            $vals = [
                $row->kelompok_kode, $row->kelompok_id, $row->kelompok_en, $row->kelompok_ket,
                $row->matriks_kode, $row->matriks_id, $row->matriks_en, $row->matriks_ket,
                $row->standard_nomor, $row->standard_judul,
                $row->point_nama, $row->point_deskripsi, $row->point_halaman, $row->point_ket, TestingPointApproval::label($row->status_approval),
                $row->item_nomor, $row->item_id, $row->item_en,
                $row->parameter_kode, $row->parameter_id, $row->satuan_kode, $row->satuan_id, $row->nilai, $row->item_ket,
            ];
            foreach ($vals as $i => $v) {
                $sheet->setCellValueExplicit([$i + 1, $r], (string) ($v ?? ''), DataType::TYPE_STRING);
            }
            $r++;
        }
        $sheet->freezePane('A2');

        $dir = storage_path('app/export-data-points');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $path = $dir . '/export_' . auth()->id() . '_' . bin2hex(random_bytes(6)) . '.xlsx';
        (new Xlsx($ss))->save($path);

        return response()->download($path, 'export_data_points_' . date('Ymd_His') . '.xlsx')->deleteFileAfterSend(true);
    }

    /** Join dasar: Kelompok → Matriks → Standard → Testing Point → Testing Item (+ Parameter & Satuan). */
    private function query()
    {
        return DB::table('testing_items as ti')
            ->join('testing_points as tp', 'tp.id_testing_point', '=', 'ti.id_testing_point')
            ->join('testing_standards as ts', 'ts.id_testing_standard', '=', 'tp.id_testing_standard')
            ->join('testing_matriks_samples as tm', 'tm.id_testing_matriks_sample', '=', 'tp.id_testing_matriks_sample')
            ->leftJoin('testing_kelompok_matriks_samples as tk', 'tk.id_testing_kelompok_matriks_sample', '=', 'tm.id_testing_kelompok_matriks_sample')
            ->leftJoin('testing_parameters as par', 'par.id_testing_parameter', '=', 'ti.id_testing_parameter')
            ->leftJoin('testing_units as un', 'un.id_testing_unit', '=', 'ti.id_testing_unit')
            ->where('ti.is_aktif', 1)
            ->where('tp.is_aktif', 1)->whereNull('tp.deleted_at')
            ->where('ts.is_aktif', 1)->whereNull('ts.deleted_at')
            ->whereNull('tm.deleted_at')
            ->whereNull('tk.deleted_at')
            ->whereNull('par.deleted_at')
            ->select([
                'tk.kode as kelompok_kode', 'tk.judul_indonesia as kelompok_id', 'tk.judul_inggris as kelompok_en', 'tk.keterangan as kelompok_ket',
                'tm.kode as matriks_kode', 'tm.judul_indonesia as matriks_id', 'tm.judul_inggris as matriks_en', 'tm.keterangan as matriks_ket',
                'ts.nomor as standard_nomor', 'ts.judul as standard_judul',
                'tp.nama as point_nama', 'tp.deskripsi as point_deskripsi', 'tp.nomor_halaman as point_halaman', 'tp.keterangan as point_ket', 'tp.status_approval',
                'ti.nomor as item_nomor', 'ti.judul_indonesia as item_id', 'ti.judul_inggris as item_en',
                'par.kode as parameter_kode', 'par.judul_indonesia as parameter_id',
                'un.kode as satuan_kode', 'un.judul_indonesia as satuan_id',
                'ti.nilai', 'ti.keterangan as item_ket',
            ]);
    }
}
