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

    /**
     * Poin aktif yang bisa ditambahkan, dikelompokkan per kategori di frontend.
     * Dengan ?id_sq=, tiap poin ditandai `dipakai` kalau sudah ada di keterangan SQ itu
     * (supaya tidak bisa dipilih dua kali — sebelumnya baru ditolak 422 setelah dipilih).
     */
    public function katalog(Request $request)
    {
        $data = DB::table('poin_keterangan')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->orderBy('kategori')
            ->orderBy('id_poin')
            ->get(['id_poin', 'kategori', 'template']);

        $dipakai = $request->filled('id_sq')
            ? DB::table('sq_keterangan')->where('id_sq', $request->id_sq)->pluck('id_poin')->all()
            : [];

        return response()->json($data->map(fn ($p) => [
            'id'             => $p->id_poin,
            'kategori'       => $p->kategori,
            'kategori_label' => Tpl::KATEGORI[$p->kategori] ?? $p->kategori,
            'text'           => Tpl::render($p->template),
            'dipakai'        => in_array($p->id_poin, $dipakai, true),
        ])->values());
    }

    /** Opsi isian bertipe rekening/personel (di sini supaya cukup permission SQ). */
    public function opsi(Request $request)
    {
        $q = trim((string) $request->q);

        if ($request->tipe === 'rekening') {
            $rows = DB::table('bank_accounts')->whereNull('deleted_at')->where('is_aktif', 1)
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                    ->where('bank', 'like', "%{$q}%")->orWhere('no_rekening', 'like', "%{$q}%")->orWhere('atas_nama', 'like', "%{$q}%")))
                ->orderBy('bank')->limit(30)->get();
            return response()->json($rows->map(fn ($b) => ['id' => $b->id_bank_account, 'text' => SqKeterangan::formatRekening($b)]));
        }

        if ($request->tipe === 'user') {
            $rows = DB::table('users')->where('is_active', 1)
                ->when($q !== '', fn ($w) => $w->where('name', 'like', "%{$q}%"))
                ->orderBy('name')->limit(30)->get(['id', 'name', 'no_hp']);
            return response()->json($rows->map(fn ($u) => ['id' => $u->id, 'text' => trim($u->name . ' ' . ($u->no_hp ?? ''))]));
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
            'nilai'      => ($awal = SqKeterangan::nilaiAwal((int) $id, $poin->template)) ? json_encode($awal) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Poin ditambahkan']);
    }

    /**
     * Ganti varian: poin pada baris ini diganti poin lain tanpa mengubah posisi/urutan.
     * Nilai isian dibawa ke varian baru untuk kunci yang sama & tipe yang sama (mis. {hari:angka});
     * isian baru diisi nilai awal otomatis (rekening utama / kontak SQ).
     */
    public function ganti(Request $request, $id, $row)
    {
        if ($lock = SqLock::bySq($id)) return $lock;
        $request->validate(['id_poin' => 'required|integer']);

        $k = DB::table('sq_keterangan as k')
            ->join('poin_keterangan as p', 'p.id_poin', '=', 'k.id_poin')
            ->where('k.id_sq', $id)->where('k.id_sq_keterangan', $row)
            ->first(['k.nilai', 'p.template']);
        if (!$k) return response()->json(['message' => 'Poin tidak ditemukan'], 404);

        $baru = DB::table('poin_keterangan')->where('id_poin', $request->id_poin)->whereNull('deleted_at')->where('is_aktif', 1)->first();
        if (!$baru) return response()->json(['message' => 'Poin pengganti tidak ditemukan atau sudah nonaktif.'], 422);

        if (DB::table('sq_keterangan')->where('id_sq', $id)->where('id_poin', $baru->id_poin)->where('id_sq_keterangan', '!=', $row)->exists()) {
            return response()->json(['message' => 'Poin pengganti sudah ada di keterangan SQ ini.'], 422);
        }

        // Bawa nilai lama yang kunci & tipenya sama dengan isian di varian baru
        $lama = $k->nilai ? (json_decode($k->nilai, true) ?: []) : [];
        $tipeLama = collect(Tpl::parse($k->template))->pluck('tipe', 'key');
        $nilai = [];
        foreach (Tpl::parse($baru->template) as $i) {
            if (array_key_exists($i['key'], $lama) && ($tipeLama[$i['key']] ?? null) === $i['tipe']) {
                $nilai[$i['key']] = $lama[$i['key']];
            }
        }
        // Isian baru yang bisa diisi otomatis (rekening utama / kontak SQ) — tidak menimpa nilai yang dibawa
        $nilai += SqKeterangan::nilaiAwal((int) $id, $baru->template) ?? [];

        DB::table('sq_keterangan')->where('id_sq_keterangan', $row)->update([
            'id_poin'    => $baru->id_poin,
            'nilai'      => $nilai ? json_encode($nilai) : null,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Varian poin diganti']);
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
            } elseif ($i['tipe'] === 'user') {
                if (!DB::table('users')->where('id', $v)->where('is_active', 1)->exists()) {
                    return response()->json(['message' => 'User tidak ditemukan atau tidak aktif.'], 422);
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
