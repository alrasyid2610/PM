<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master data Personnel — orang lapangan (PIC, teknisi sampling, dsb) yang
 * bisa ditugaskan di Personel FWO, TAPI tidak otomatis bisa login ke sistem.
 * Beda dari `users` yang khusus akun sistem (admin/finance/sales).
 *
 * `id_user` nullable — jembatan opsional kalau suatu saat personnel ini
 * perlu diberi akses login (lihat ProfileController/UserController untuk
 * akun sistem). Default NULL = tidak bisa akses sistem sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->increments('id_personnel');
            $table->string('nama', 255);
            $table->string('no_hp', 30)->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->boolean('is_aktif')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};
