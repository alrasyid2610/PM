<?php

namespace App\Support\DataImport;

/**
 * Daftar jenis import di menu Import Data. Jenis baru = satu kelas `ImportDefinition` + satu baris di sini.
 */
class ImportRegistry
{
    /** @return array<string, ImportDefinition> */
    public static function all(): array
    {
        $defs = [
            new SamplingPointImport('env'),
            new SamplingPointImport('we'),
        ];

        $out = [];
        foreach ($defs as $d) $out[$d->key()] = $d;
        return $out;
    }

    public static function get(?string $key): ?ImportDefinition
    {
        return self::all()[$key] ?? null;
    }
}
