<?php

namespace App\Http\Controllers;

use App\Support\PoinKeteranganTemplate as Tpl;
use App\Support\SqKeterangan;
use App\Support\SqLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Keterangan penawaran SQ terstruktur — poin dipilih dari master `poin_keterangan`,
 * isian (hari, rekening, kontak) diubah per SQ. Semua perubahan hanya untuk SQ Draft (SqLock).
 *
 * Catatan permission: middleware memetakan POST→can_create, PUT→can_update, DELETE→can_delete.
 * Mengubah keterangan = mengedit SQ, jadi semua aksi tulis di sini memakai PUT (can_update).
 */
class SqKeteranganController extends Controller
{
    public function index($id)
    {
        $sq = DB::table('sales_quotations')->where('id_sq', $id)->first(['status']);
        if (!$sq) return response()->json(['message' => 'Sales Quotation tidak ditemukan'], 404);

        return response()->json([
            'status' => $sq->status,
            'data'   => SqKeterangan::rows((int) $id)->values(),
            // pilihan pemicu tahap pembayaran (tipe isian termin)
            'pemicu' => collect(Tpl::PEMICU_TERMIN)->map(fn ($p, $k) => ['value' => $k, 'label' => $p[0]])->values(),
        ]);
    }

    /** Poin aktif yang bisa ditambahkan, dikelompokkan per kategori di frontend. */
    public function katalog()
    {
        $data = DB::table('poin_keterangan')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->orderBy('kategori')
            ->orderBy('id_poin')
            ->get(['id_poin', 'kategori', 'template']);

        return response()->json($data->map(fn ($p) => [
            'id'             => $p->id_poin,
            'kategori'       => $p->kategori,
            'kategori_label' => Tpl::KATEGORI[$p->kategori] ?? $p->kategori,
            'text'           => Tpl::render($p->template),
        ])->values());
    }

    /** Opsi isian bertipe rekening/personel (di sini supaya cukup permission SQ). */
    public function opsi(Request $request)
    {
        $q = trim((string) $request->q);

        if ($request->tipe === 'rekening') {
            $rows = DB::table('bank_accounts')->whereNull('deleted_at')
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                    ->where('bank', 'like', "%{$q}%")->orWhere('no_rekening', 'like', "%{$q}%")->orWhere('atas_nama', 'like', "%{$q}%")))
                ->orderBy('bank')->limit(30)->get();
            return response()->json($rows->map(fn ($b) => ['id' => $b->id_bank_account, 'text' => SqKeterangan::formatRekening($b)]));
        }

        if ($request->tipe === 'personel') {
            $rows = DB::table('personnel')->whereNull('deleted_at')->where('is_aktif', 1)
                ->when($q !== '', fn ($w) => $w->where('nama', 'like', "%{$q}%"))
                ->orderBy('nama')->limit(30)->get(['id_personnel', 'nama', 'no_hp']);
            return response()->json($rows->map(fn ($p) => ['id' => $p->id_personnel, 'text' => trim($p->nama . ' ' . ($p->no_hp ?? ''))]));
        }

        return response()->json([]);
    }

    public function tambah(Request $request, $id)
    {
        if ($lock = SqLock::bySq($id)) return $lock;
        $request->validate(['id_poin' => 'required|integer']);

        $poin = DB::table('poin_keterangan')->where('id_poin', $request->id_poin)->whereNull('deleted_at')->where('is_aktif', 1)->first();
        if (!$poin) return response()->json(['message' => 'Poin keterangan tidak ditemukan atau sudah nonaktif.'], 422);

        if (DB::table('sq_keterangan')->where('id_sq', $id)->where('id_poin', $poin->id_poin)->exists()) {
            return response()->json(['message' => 'Poin tersebut sudah ada di keterangan SQ ini.'], 422);
        }

        DB::table('sq_keterangan')->insert([
            'id_sq'      => $id,
            'id_poin'    => $poin->id_poin,
            'urutan'     => (int) DB::table('sq_keterangan')->where('id_sq', $id)->max('urutan') + 1,
            'nilai'      => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Poin ditambahkan']);
    }

    public function isiStandar($id)
    {
        if ($lock = SqLock::bySq($id)) return $lock;
        $n = SqKeterangan::isiStandar((int) $id);

        return response()->json([
            'success' => true,
            'message' => $n > 0 ? "{$n} poin standar ditambahkan" : 'Semua poin standar sudah ada',
        ]);
    }

    public function urut(Request $request, $id)
    {
        if ($lock = SqLock::bySq($id)) return $lock;
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        foreach (array_values($request->ids) as $i => $rowId) {
            DB::table('sq_keterangan')->where('id_sq', $id)->where('id_sq_keterangan', $rowId)
                ->update(['urutan' => $i + 1, 'updated_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id, $row)
    {
        if ($lock = SqLock::bySq($id)) return $lock;
        $request->validate(['nilai' => 'nullable|array']);

        $k = DB::table('sq_keterangan as k')
            ->join('poin_keterangan as p', 'p.id_poin', '=', 'k.id_poin')
            ->where('k.id_sq', $id)->where('k.id_sq_keterangan', $row)
            ->first(['k.nilai', 'p.template']);
        if (!$k) return response()->json(['message' => 'Poin tidak ditemukan'], 404);

        $nilai = $k->nilai ? (json_decode($k->nilai, true) ?: []) : [];
        $input = $request->input('nilai', []);

        foreach (Tpl::parse($k->template) as $i) {
            if (!array_key_exists($i['key'], $input)) continue;
            $v = $input[$i['key']];

            if ($v === null || $v === '') { unset($nilai[$i['key']]); continue; }

            if ($i['tipe'] === 'angka') {
                if (!ctype_digit((string) $v) || (int) $v > 9999) {
                    return response()->json(['message' => "Isian '" . str_replace('_', ' ', $i['key']) . "' harus berupa angka bulat 0–9999."], 422);
                }
                $nilai[$i['key']] = (int) $v;
            } elseif ($i['tipe'] === 'termin') {
                // daftar tahap [{persen, pemicu}]; total harus 100% hanya dicek saat Finalkan
                $tahap = [];
                foreach ((array) $v as $t) {
                    $persen = $t['persen'] ?? null;
                    if (!ctype_digit((string) $persen) || (int) $persen < 1 || (int) $persen > 100) {
                        return response()->json(['message' => 'Persentase tahap harus angka bulat 1–100.'], 422);
                    }
                    if (!isset(Tpl::PEMICU_TERMIN[$t['pemicu'] ?? ''])) {
                        return response()->json(['message' => 'Pemicu tahap pembayaran tidak valid.'], 422);
                    }
                    $tahap[] = ['persen' => (int) $persen, 'pemicu' => $t['pemicu']];
                }
                if ($tahap) $nilai[$i['key']] = $tahap; else unset($nilai[$i['key']]);
            } elseif ($i['tipe'] === 'rekening') {
                if (!DB::table('bank_accounts')->where('id_bank_account', $v)->whereNull('deleted_at')->exists()) {
                    return response()->json(['message' => 'Rekening tidak ditemukan.'], 422);
                }
                $nilai[$i['key']] = (int) $v;
            } elseif ($i['tipe'] === 'personel') {
                if (!DB::table('personnel')->where('id_personnel', $v)->whereNull('deleted_at')->exists()) {
                    return response()->json(['message' => 'Personel tidak ditemukan.'], 422);
                }
                $nilai[$i['key']] = (int) $v;
            }
        }

        DB::table('sq_keterangan')->where('id_sq_keterangan', $row)->update([
            'nilai'      => $nilai ? json_encode($nilai) : null,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function hapus($id, $row)
    {
        if ($lock = SqLock::bySq($id)) return $lock;
        DB::table('sq_keterangan')->where('id_sq', $id)->where('id_sq_keterangan', $row)->delete();

        return response()->json(['success' => true, 'message' => 'Poin dihapus']);
    }
}
