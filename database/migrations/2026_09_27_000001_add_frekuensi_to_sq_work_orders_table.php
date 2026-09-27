<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Frekuensi WO estimasi — sama seperti work_orders.interval_bulan /
    // no_urut_period (1=Bulanan, 2=Bimulanan, 3=Triwulan, 4=Caturwulan,
    // 6=Semester, 12=Annual). Nullable: WO tanpa pengulangan.
    public function up(): void
    {
        Schema::table('sq_work_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('interval_bulan')->nullable()->after('durasi_hari');
            $table->unsignedSmallInteger('no_urut_period')->nullable()->after('interval_bulan');
        });
    }

    public function down(): void
    {
        Schema::table('sq_work_orders', function (Blueprint $table) {
            $table->dropColumn(['interval_bulan', 'no_urut_period']);
        });
    }
};
