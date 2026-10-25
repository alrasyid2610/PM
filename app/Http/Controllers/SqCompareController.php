<?php

namespace App\Http\Controllers;

use App\Support\SqCompare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * Perbandingan dua Sales Quotation (menu "Bandingkan SQ" di detail SQ).
 * "Ini" = SQ yang dibuka (sesudah), "pembanding" = SQ lain (sebelum). Hanya baca.
 */
class SqCompareController extends Controller
{
    private function label(object $r): string
    {
        $status = ['draft' => 'Draft', 'pending' => 'Menunggu Approval', 'final' => 'Final', 'win' => 'Win', 'lose' => 'Lose', 'void' => 'Void'][$r->status] ?? $r->status;
        return $r->no_sq . ' Rev.' . $r->revisi . ' · ' . $status;
    }

    /** Hasil perbandingan SQ {id} (ini) dengan SQ {other} (pembanding). */
    public function compare($id, $other)
    {
        if ((int) $id === (int) $other) {
            return response()->json(['message' => 'Pilih SQ pembanding yang berbeda.'], 422);
        }
        $hasil = SqCompare::compare((int) $id, (int) $other);
        if (!$hasil) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);

        return response()->json($hasil);
    }

    /**
     * Pilihan SQ pembanding untuk select2: revisi lain dalam keluarga SQ ini lebih dulu,
     * lalu SQ lain (revisi terbaru, bisa dicari). `default` = revisi tepat sebelumnya.
     */
    public function options(Request $request, $id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->whereNull('deleted_at')->first();
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);

        $root = $sq->id_sq_induk ?: $sq->id_sq;
        $keluarga = DB::table('sales_quotations')
            ->whereNull('deleted_at')
            ->where(fn ($q) => $q->where('id_sq', $root)->orWhere('id_sq_induk', $root))
            ->where('id_sq', '!=', $sq->id_sq)
            ->orderByDesc('revisi')
            ->get(['id_sq', 'no_sq', 'revisi', 'status']);

        $term = trim((string) $request->query('q', ''));
        $lain = DB::table('sales_quotations as s')
            ->whereNull('s.deleted_at')
            ->where('s.is_latest', true)
            ->whereNotIn('s.id_sq', $keluarga->pluck('id_sq')->push($sq->id_sq))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('s.no_sq', 'like', "%{$term}%")->orWhere('s.judul_order', 'like', "%{$term}%")))
            ->orderByDesc('s.id_sq')
            ->limit(20)
            ->get(['s.id_sq', 's.no_sq', 's.revisi', 's.status', 's.judul_order']);

        $map = fn ($r) => ['id' => $r->id_sq, 'text' => $this->label($r) . (isset($r->judul_order) && $r->judul_order ? ' — ' . $r->judul_order : '')];
        $results = [];
        $revisiLain = $keluarga->filter(fn ($r) => $term === '' || stripos($r->no_sq . ' rev.' . $r->revisi, $term) !== false)->map($map)->values();
        if ($revisiLain->isNotEmpty()) $results[] = ['text' => 'Revisi lain SQ ini', 'children' => $revisiLain];
        if ($lain->isNotEmpty()) $results[] = ['text' => 'SQ lain', 'children' => $lain->map($map)->values()];

        $sebelum = $keluarga->where('revisi', '<', $sq->revisi)->sortByDesc('revisi')->first();
        return response()->json([
            'results' => $results,
            'default' => $sebelum ? $map($sebelum) : null,
        ]);
    }

    /**
     * PDF perbandingan (internal). Isinya sama dengan modal; default hanya baris yang
     * berubah, `?semua=1` menyertakan baris yang sama. Memuat biaya & margin, jadi diberi label internal.
     */
    public function pdf(Request $request, $id, $other)
    {
        if ((int) $id === (int) $other) abort(422, 'Pilih SQ pembanding yang berbeda.');
        $d = SqCompare::compare((int) $id, (int) $other);
        if (!$d) abort(404, 'Sales Quotation tidak ditemukan');

        $semua = $request->boolean('semua');
        if (!$semua) {
            $beda = fn (array $rows) => array_values(array_filter($rows, fn ($r) => $r['status'] !== 'sama'));
            $d['header'] = $beda($d['header']);
            $d['keterangan'] = $beda($d['keterangan']);
            $d['wos'] = array_values(array_filter(array_map(function ($w) use ($beda) {
                if ($w['status'] === 'sama') return null;
                foreach ($w['sections'] as $k => $rows) $w['sections'][$k] = $beda($rows);
                return $w;
            }, $d['wos'])));
        }

        $nama = 'Perbandingan-' . $d['ini']['no_sq'] . '-Rev' . $d['ini']['revisi'] . '-vs-Rev' . $d['pembanding']['revisi'] . '.pdf';

        return Pdf::view('pdf.sales-quotation.compare', [
            'd'        => $d,
            'semua'    => $semua,
            'pencetak' => auth()->user()->name ?? '—',
        ])
            ->headerView('pdf.layouts.sections.header')
            ->footerView('pdf.layouts.sections.footer-internal')
            ->margins(top: 30, right: 0, bottom: 15, left: 0)
            ->format('a4')
            ->name($nama);
    }
}
