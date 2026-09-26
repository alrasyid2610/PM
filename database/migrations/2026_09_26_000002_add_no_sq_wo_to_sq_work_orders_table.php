<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Nomor identitas SQ Work Order (format `SQWO-YY-NNNN`, penomoran global —
 * sama pola dengan `work_orders.no_wo`), supaya bisa ditampilkan di daftar
 * ringkas tab "Work Order" pada SQ dan di halaman SQ Work Order sendiri.
 * Baris yang sudah ada sebelum migration ini (dibuat sebelum fitur nomor
 * ditambahkan) di-backfill di sini, diurutkan by id_sq_wo supaya tetap
 * konsisten dengan cara generateNoSqWo() di controller menentukan nomor
 * berikutnya (ORDER BY id_sq_wo, bukan created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sq_work_orders', function (Blueprint $table) {
            $table->string('no_sq_wo', 50)->nullable()->after('id_sq_wo');
        });

        $rowsByYear = DB::table('sq_work_orders')
            ->whereNull('no_sq_wo')
            ->orderBy('id_sq_wo')
            ->get(['id_sq_wo', 'created_at'])
            ->groupBy(fn($row) => date('y', strtotime($row->created_at)));

        foreach ($rowsByYear as $year => $rows) {
            $seq = 1;
            foreach ($rows as $row) {
                DB::table('sq_work_orders')->where('id_sq_wo', $row->id_sq_wo)->update([
                    'no_sq_wo' => "SQWO-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT),
                ]);
                $seq++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('sq_work_orders', function (Blueprint $table) {
            $table->dropColumn('no_sq_wo');
        });
    }
};
