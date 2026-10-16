<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keterangan penawaran SQ terstruktur (tabel `sq_keterangan` + master `poin_keterangan`).
 * Render teks: SQ Draft dirender langsung dari master + nilai; saat Final teks dikunci
 * ke `teks_final` (lihat snapshot()).
 */
class SqKeterangan
{
    /** Baris keterangan SQ lengkap dengan isian, label nilai & teks akhir. */
    public static function rows(int $idSq): Collection
    {
        $rows = DB::table('sq_keterangan as k')
            ->join('poin_keterangan as p', 'p.id_poin', '=', 'k.id_poin')
            ->where('k.id_sq', $idSq)
            ->orderBy('k.urutan')
            ->orderBy('k.id_sq_keterangan')
            ->get(['k.id_sq_keterangan', 'k.id_poin', 'k.urutan', 'k.nilai', 'k.teks_final', 'p.kategori', 'p.template']);

        $banks = DB::table('bank_accounts')->whereNull('deleted_at')->get()->keyBy('id_bank_account');
        $users = DB::table('users')->get(['id', 'name', 'no_hp'])->keyBy('id');

        return $rows->map(function ($r) use ($banks, $users) {
            $nilai = $r->nilai ? (json_decode($r->nilai, true) ?: []) : [];
            $display = [];
            $isian = [];
            foreach (PoinKeteranganTemplate::parse($r->template) as $i) {
                $val = $nilai[$i['key']] ?? null;
                $label = null;
                if ($i['tipe'] === 'angka') {
                    $label = ($val !== null && $val !== '') ? (string) $val : $i['default'];
                } elseif ($i['tipe'] === 'rekening' && $val && ($b = $banks->get($val))) {
                    $label = self::formatRekening($b);
                } elseif ($i['tipe'] === 'user' && $val && ($u = $users->get($val))) {
                    $label = trim($u->name . ' ' . ($u->no_hp ?? ''));
                }

                $lengkap = $label !== null && $label !== '';
                if ($i['tipe'] === 'termin') {
                    $tahap = is_array($val) ? $val : [];
                    $label = self::formatTermin($tahap);
                    // Lengkap hanya bila ada tahap, semua valid, dan total tepat 100%
                    $lengkap = $tahap && collect($tahap)->sum('persen') === 100;
                    // Tahap yang sudah diisi tetap ditampilkan walau total belum 100% (Final tetap diblokir)
                    if (!$tahap) $label = null;
                }

                if ($label !== null && $label !== '') $display[$i['key']] = $label;
                $isian[] = $i + [
                    'value'       => $val,
                    'value_label' => $label,
                    'lengkap'     => $lengkap,
                ];
            }

            $teks = $r->teks_final ?? PoinKeteranganTemplate::render($r->template, $display);

            return (object) [
                'id_sq_keterangan' => $r->id_sq_keterangan,
                'id_poin'          => $r->id_poin,
                'urutan'           => $r->urutan,
                'kategori'         => $r->kategori,
                'kategori_label'   => PoinKeteranganTemplate::KATEGORI[$r->kategori] ?? $r->kategori,
                'template'         => $r->template,
                'isian'            => $isian,
                'teks'             => $teks,
                'dikunci'          => $r->teks_final !== null,
                'lengkap'          => collect($isian)->every(fn ($i) => $i['lengkap']),
            ];
        });
    }

    /**
     * No. SQ Draft yang masih memakai rekening ini pada isian bertipe rekening.
     * SQ Final tidak dihitung: teksnya sudah dikunci (teks_final).
     */
    public static function sqPemakaiRekening(int $idRekening): array
    {
        $rows = DB::table('sq_keterangan as k')
            ->join('poin_keterangan as p', 'p.id_poin', '=', 'k.id_poin')
            ->join('sales_quotations as s', 's.id_sq', '=', 'k.id_sq')
            ->whereNull('k.teks_final')
            ->whereNotNull('k.nilai')
            ->get(['k.nilai', 'p.template', 's.no_sq']);

        $dipakai = [];
        foreach ($rows as $r) {
            $nilai = json_decode($r->nilai, true) ?: [];
            foreach (PoinKeteranganTemplate::parse($r->template) as $i) {
                if ($i['tipe'] === 'rekening' && (int) ($nilai[$i['key']] ?? 0) === $idRekening) {
                    $dipakai[$r->no_sq] = true;
                    break;
                }
            }
        }

        return array_keys($dipakai);
    }

    public static function formatRekening(object $b): string
    {
        $bank = preg_match('/^bank\s/i', $b->bank) ? $b->bank : 'Bank ' . $b->bank;
        return "{$bank}, a.n {$b->atas_nama}, No. Rekening : {$b->no_rekening}";
    }

    /** Daftar tahap pembayaran → teks baris per tahap (diawali baris baru, dipakai setelah judul poin). */
    public static function formatTermin(array $tahap): string
    {
        $baris = [];
        foreach (array_values($tahap) as $n => $t) {
            [$teks, $dp] = PoinKeteranganTemplate::PEMICU_TERMIN[$t['pemicu'] ?? ''] ?? [null, false];
            if ($teks === null) continue;
            $baris[] = '- Tahap ' . ($n + 1) . ' : ' . ($dp ? 'DP ' : '') . (int) ($t['persen'] ?? 0) . '% ' . $teks;
        }
        return $baris ? "\n" . implode("\n", $baris) : '';
    }

    /**
     * Nilai awal isian saat poin ditambahkan ke SQ (supaya user tidak memilih ulang di setiap SQ):
     *  - tipe `rekening` → Rekening Utama di master Bank Account (kalau ada)
     *  - tipe `user`     → Marketing Internal SQ, cadangan PIC Input (hanya user aktif)
     * Tipe lain (angka/termin) dibiarkan kosong → memakai default template / diisi user.
     * Mengembalikan array nilai (kunci => id) atau null bila tidak ada yang bisa diisi otomatis.
     */
    public static function nilaiAwal(int $idSq, string $template): ?array
    {
        $isian = PoinKeteranganTemplate::parse($template);
        $tipe  = array_column($isian, 'tipe');
        if (!array_intersect(['rekening', 'user'], $tipe)) return null;

        $rekening = null;
        if (in_array('rekening', $tipe, true)) {
            // Pengisian otomatis hanya kemudahan: kalau gagal (mis. migration kolom is_utama belum
            // dijalankan) jangan sampai membuat SQ / menambah poin ikut gagal — isian dibiarkan kosong.
            try {
                $rekening = DB::table('bank_accounts')->whereNull('deleted_at')->where('is_utama', 1)->where('is_aktif', 1)->value('id_bank_account');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('SqKeterangan::nilaiAwal rekening utama gagal: ' . $e->getMessage());
            }
        }

        $kontak = null;
        if (in_array('user', $tipe, true)) {
            $sq = DB::table('sales_quotations')->where('id_sq', $idSq)->first(['pic_marketing_internal', 'pic_input']);
            foreach ([$sq->pic_marketing_internal ?? null, $sq->pic_input ?? null] as $uid) {
                if ($uid && DB::table('users')->where('id', $uid)->where('is_active', 1)->exists()) { $kontak = (int) $uid; break; }
            }
        }

        $nilai = [];
        foreach ($isian as $i) {
            if ($i['tipe'] === 'rekening' && $rekening) $nilai[$i['key']] = (int) $rekening;
            if ($i['tipe'] === 'user' && $kontak)       $nilai[$i['key']] = $kontak;
        }

        return $nilai ?: null;
    }

    /** Isi paket standar (poin aktif ber-is_default) yang belum ada di SQ. Mengembalikan jumlah yang ditambahkan. */
    public static function isiStandar(int $idSq): int
    {
        $ada = DB::table('sq_keterangan')->where('id_sq', $idSq)->pluck('id_poin')->all();
        $urutan = (int) DB::table('sq_keterangan')->where('id_sq', $idSq)->max('urutan');

        $poin = DB::table('poin_keterangan')
            ->whereNull('deleted_at')
            ->where('is_aktif', 1)
            ->where('is_default', 1)
            ->whereNotIn('id_poin', $ada ?: [0])
            ->orderBy('urutan_default')
            ->orderBy('id_poin')
            ->get();

        foreach ($poin as $p) {
            $awal = self::nilaiAwal($idSq, $p->template);
            DB::table('sq_keterangan')->insert([
                'id_sq'      => $idSq,
                'id_poin'    => $p->id_poin,
                'urutan'     => ++$urutan,
                'nilai'      => $awal ? json_encode($awal) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $poin->count();
    }

    /** Daftar poin yang isiannya belum lengkap (untuk validasi Finalkan). */
    public static function yangBelumLengkap(int $idSq): array
    {
        return self::rows($idSq)
            ->reject(fn ($r) => $r->lengkap)
            ->map(fn ($r) => $r->kategori_label . ': ' . PoinKeteranganTemplate::render($r->template))
            ->values()
            ->all();
    }

    /** Kunci teks akhir semua poin (dipanggil saat Finalkan). */
    public static function snapshot(int $idSq): void
    {
        foreach (self::rows($idSq) as $r) {
            DB::table('sq_keterangan')->where('id_sq_keterangan', $r->id_sq_keterangan)
                ->update(['teks_final' => $r->teks, 'updated_at' => now()]);
        }
    }

    /** Salin ke SQ lain (revisi). Teks terkunci tidak ikut — SQ baru berstatus Draft. */
    public static function salin(int $dariSq, int $keSq): void
    {
        foreach (DB::table('sq_keterangan')->where('id_sq', $dariSq)->orderBy('urutan')->get() as $k) {
            DB::table('sq_keterangan')->insert([
                'id_sq'      => $keSq,
                'id_poin'    => $k->id_poin,
                'urutan'     => $k->urutan,
                'nilai'      => $k->nilai,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
