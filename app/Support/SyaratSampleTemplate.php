<?php

namespace App\Support;

/**
 * Template teks "Syarat Sample" — sintaks sama dengan Poin Keterangan SQ ({kunci:tipe=default}), tapi tipe isian
 * dibatasi ke `angka` (jumlah botol, volume, ukuran, dst). Parse/render memakai ulang PoinKeteranganTemplate.
 * Contoh: "Volume air harus lebih dari {volume:angka=100} ml".
 */
class SyaratSampleTemplate
{
    public const TIPE = ['angka'];

    public static function parse(string $template): array
    {
        return PoinKeteranganTemplate::parse($template);
    }

    /** Pesan error bila template tidak valid, null bila valid. */
    public static function validate(string $template): ?string
    {
        if ($msg = PoinKeteranganTemplate::validate($template)) {
            return $msg;
        }
        foreach (self::parse($template) as $p) {
            if (!in_array($p['tipe'], self::TIPE, true)) {
                return "Tipe isian '{$p['tipe']}' belum bisa dipakai di Syarat Sample. Tipe yang tersedia: " . implode(', ', self::TIPE) . '.';
            }
        }
        return null;
    }

    /**
     * Render teks akhir. $nilai = [kunci => nilai]; isian tanpa nilai memakai default template,
     * atau tanda "[kunci]" bila belum ada.
     */
    public static function render(string $template, array $nilai = []): string
    {
        return PoinKeteranganTemplate::render($template, $nilai);
    }
}
