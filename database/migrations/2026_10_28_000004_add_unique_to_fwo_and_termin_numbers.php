<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Unique index untuk nomor FWO (fieldworks.no_fwo) dan nomor Termin (termin.no_termin).
 * Sebelumnya hanya SO, WO, SQ, SQWO, SQFWO, dan Kontrak yang unique, jadi dua permintaan simultan
 * bisa menyimpan nomor yang sama diam-diam. Dengan index ini, DocumentNumber::insertGetId()
 * mengambil nomor baru saat terjadi bentrok. NULL tetap boleh berulang.
 *
 * Migration berhenti dengan pesan jelas bila masih ada nomor ganda — rapikan dulu datanya.
 */
return new class extends Migration
{
    private array $targets = [
        ['fieldworks', 'no_fwo', 'fieldworks_no_fwo_unique'],
        ['termin', 'no_termin', 'termin_no_termin_unique'],
    ];

    public function up(): void
    {
        foreach ($this->targets as [$tabel, $kolom, $index]) {
            if (Schema::hasIndex($tabel, $index)) {
                continue;
            }

            $ganda = DB::table($tabel)
                ->select($kolom, DB::raw('COUNT(*) as jumlah'))
                ->whereNotNull($kolom)
                ->groupBy($kolom)
                ->having('jumlah', '>', 1)
                ->limit(10)
                ->pluck('jumlah', $kolom);

            if ($ganda->isNotEmpty()) {
                $daftar = $ganda->map(fn ($n, $no) => "$no ($n baris)")->implode(', ');
                throw new RuntimeException("Tidak bisa menambah unique index {$tabel}.{$kolom}: ada nomor ganda → {$daftar}. Perbaiki datanya dulu.");
            }

            Schema::table($tabel, function (Blueprint $table) use ($kolom, $index) {
                $table->unique($kolom, $index);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->targets as [$tabel, $kolom, $index]) {
            if (Schema::hasIndex($tabel, $index)) {
                Schema::table($tabel, function (Blueprint $table) use ($index) {
                    $table->dropUnique($index);
                });
            }
        }
    }
};
