<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Output Pekerjaan: penanda LHU + penghubung ke sample FWO (2026-10-28).
 *  - output_pekerjaan.is_lhu: output ini LHU atau bukan (otomatis tercentang di UI bila judul mengandung "LHU").
 *  - output_pekerjaan_samples: sample (lab_samples) yang dicakup LHU itu. Satu sample hanya boleh terhubung ke SATU
 *    output (unique id_lab_sample). Terhapus otomatis bila output atau sample-nya dihapus (FK cascade).
 * Data lama: output yang judulnya mengandung "LHU" ditandai is_lhu = 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('output_pekerjaan', function (Blueprint $table) {
            $table->boolean('is_lhu')->default(false)->after('judul_output');
        });

        DB::table('output_pekerjaan')->where('judul_output', 'like', '%LHU%')->update(['is_lhu' => 1]);

        Schema::create('output_pekerjaan_samples', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_output');
            $table->unsignedInteger('id_lab_sample');
            $table->timestamps();

            $table->unique('id_lab_sample');
            $table->index('id_output');
            $table->foreign('id_output')->references('id_output')->on('output_pekerjaan')->cascadeOnDelete();
            $table->foreign('id_lab_sample')->references('id_lab_sample')->on('lab_samples')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('output_pekerjaan_samples');

        Schema::table('output_pekerjaan', function (Blueprint $table) {
            $table->dropColumn('is_lhu');
        });
    }
};
