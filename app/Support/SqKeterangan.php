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
        $personnel = DB::table('personnel')->whereNull('deleted_at')->get(['id_personnel', 'nama', 'no_hp'])->keyBy('id_personnel');

        return $rows->map(function ($r) use ($banks, $personnel) {
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
                } elseif ($i['tipe'] === 'personel' && $val && ($p = $personnel->get($val))) {
                    $label = trim($p->nama . ' ' . ($p->no_hp ?? ''));
                }

                $lengkap = $label !== null && $label !== '';
                if ($i['tipe'] === 'termin') {
                    $tahap = is_array($val) ? $val : [];
                    $label = self::formatTermin($tahap);
                    // Lengkap hanya bila ada tahap, semua valid, dan total tepat 100%
                    $lengkap = $tahap && collect($tahap)->sum('persen') === 100;
                    if (!$lengkap) $label = null;
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
            DB::table('sq_keterangan')->insert([
                'id_sq'      => $idSq,
                'id_poin'    => $p->id_poin,
                'urutan'     => ++$urutan,
                'nilai'      => null,
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
