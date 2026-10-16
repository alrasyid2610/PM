<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master judul untuk input Output (Output Pekerjaan & Output Other), 2026-10-17.
 * Hanya daftar pilihan: output TIDAK menyimpan id master, melainkan teks judulnya
 * (output_pekerjaan.judul_output / output_tambahan.nama_item). Judul baru otomatis
 * masuk ke master saat output disimpan. Pencocokan tidak peka huruf besar-kecil
 * (collation utf8mb4_unicode_ci), jadi unique (tipe, judul) juga tidak peka.
 *
 * Isi awal: judul unik dari output yang sudah ada; bila ada variasi huruf besar-kecil
 * dipakai bentuk yang paling sering muncul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('output_judul', function (Blueprint $table) {
            $table->id('id_output_judul');
            $table->enum('tipe', ['pekerjaan', 'other']);
            $table->string('judul', 255);
            $table->timestamps();

            $table->unique(['tipe', 'judul']);
        });

        $sumber = [
            'pekerjaan' => DB::table('output_pekerjaan')->whereNotNull('judul_output')->pluck('judul_output'),
            'other'     => DB::table('output_tambahan')->whereNull('deleted_at')->whereNotNull('nama_item')->pluck('nama_item'),
        ];

        foreach ($sumber as $tipe => $daftar) {
            $per = []; // lower(judul) => [bentuk => jumlah]
            foreach ($daftar as $j) {
                $j = trim(preg_replace('/\s+/u', ' ', (string) $j));
                if ($j === '') continue;
                $per[mb_strtolower($j)][$j] = ($per[mb_strtolower($j)][$j] ?? 0) + 1;
            }
            ksort($per);
            foreach ($per as $bentuk) {
                arsort($bentuk);
                DB::table('output_judul')->insert([
                    'tipe'       => $tipe,
                    'judul'      => array_key_first($bentuk),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('output_judul');
    }
};
