<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wo_lab_sample_regs', function (Blueprint $table) {
            $table->renameColumn('tanggal_diterima', 'tanggal_registrasi');
        });
    }

    public function down(): void
    {
        Schema::table('wo_lab_sample_regs', function (Blueprint $table) {
            $table->renameColumn('tanggal_registrasi', 'tanggal_diterima');
        });
    }
};
