<?php

namespace App\Support;

/**
 * Template teks "Poin Keterangan" (Sales Quotation, dst.).
 *
 * Sintaks isian dalam template: {kunci:tipe} atau {kunci:tipe=default}
 *   - angka      → bilangan (mis. {hari:angka=12})
 *   - rekening   → pilih dari master Bank Account
 *   - user       → pilih dari User Management (nama + no. HP user)
 *   - termin     → daftar tahap pembayaran (dipakai di fase Termin)
 * `kunci` hanya huruf kecil/angka/underscore dan harus unik per template.
 */
class PoinKeteranganTemplate
{
    public const TIPE = ['angka', 'rekening', 'user', 'termin'];

    public const KATEGORI = [
        'jadwal_sampling' => 'Jadwal Sampling',
        'waktu_laporan'   => 'Waktu Laporan / Pengerjaan',
        'biaya_sampling'  => 'Biaya Sampling',
        'dokumen_laporan' => 'Dokumen Laporan',
        'progres'         => 'Progres Pekerjaan',
        'termin'          => 'Termin Pembayaran',
        'pelunasan'       => 'Pelunasan / Jatuh Tempo',
        'rekening'        => 'Rekening Pembayaran',
        'kontak'          => 'Kontak Informasi',
    ];

    /**
     * Pemicu tahap pembayaran (tipe isian `termin`) — pilihan tetap, bukan teks bebas.
     * Format: key => [teks, tahap DP?]. Tahap DP dicetak "DP {persen}% {teks}".
     */
    public const PEMICU_TERMIN = [
        'po_diterima'      => ['saat PO diterima laboratorium', true],
        'sampling_selesai' => ['setelah sampling selesai dilakukan', false],
        'draft_dikirim'    => ['setelah draft hasil uji dikirimkan', false],
        'laporan_diterima' => ['setelah laporan hasil uji diterima oleh klien', false],
        'laporan_sem_1'    => ['setelah laporan hasil uji semester I diterima oleh klien', false],
        'laporan_sem_2'    => ['setelah laporan hasil uji semester II diterima oleh klien', false],
    ];

    private const PATTERN = '/\{([a-z0-9_]+):([a-z]+)(?:=([^{}]*))?\}/';

    /** Daftar isian dari template: [['key','tipe','default'], ...] */
    public static function parse(string $template): array
    {
        preg_match_all(self::PATTERN, $template, $m, PREG_SET_ORDER);

        return array_map(fn ($x) => [
            'key'     => $x[1],
            'tipe'    => $x[2],
            'default' => isset($x[3]) && $x[3] !== '' ? $x[3] : null,
        ], $m);
    }

    /** Pesan error bila template tidak valid, null bila valid. */
    public static function validate(string $template): ?string
    {
        // Kurung kurawal yang tersisa setelah semua isian valid dibuang = isian rusak
        if (preg_match('/[{}]/', preg_replace(self::PATTERN, '', $template))) {
            return 'Sintaks isian salah. Gunakan {kunci:tipe} atau {kunci:tipe=default}, mis. {hari:angka=12}.';
        }

        $keys = [];
        foreach (self::parse($template) as $p) {
            if (!in_array($p['tipe'], self::TIPE, true)) {
                return "Tipe isian '{$p['tipe']}' tidak dikenal. Tipe yang tersedia: " . implode(', ', self::TIPE) . '.';
            }
            if (in_array($p['key'], $keys, true)) {
                return "Kunci isian '{$p['key']}' dipakai lebih dari sekali.";
            }
            if ($p['tipe'] === 'angka' && $p['default'] !== null && !ctype_digit($p['default'])) {
                return "Nilai default '{$p['key']}' harus berupa angka bulat.";
            }
            if ($p['tipe'] !== 'angka' && $p['default'] !== null) {
                return "Isian '{$p['key']}' bertipe {$p['tipe']} tidak boleh punya nilai default.";
            }
            $keys[] = $p['key'];
        }

        return null;
    }

    /**
     * Render teks akhir. $nilai = [kunci => teks hasil]; isian tanpa nilai memakai default,
     * atau tanda "[kunci]" bila belum diisi.
     */
    public static function render(string $template, array $nilai = []): string
    {
        return preg_replace_callback(self::PATTERN, function ($m) use ($nilai) {
            $v = $nilai[$m[1]] ?? null;
            if ($v === null || $v === '') $v = isset($m[3]) && $m[3] !== '' ? $m[3] : null;
            return $v ?? '[' . str_replace('_', ' ', $m[1]) . ']';
        }, $template);
    }
}
