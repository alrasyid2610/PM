<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan login terakhir per user: waktu dan IP login berhasil terakhir (diisi AuthController::login).
 * Dipakai untuk memantau aktivitas akun di User Management. NULL = belum pernah login sejak fitur ini aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('status_kepegawaian');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'last_login_ip']);
        });
    }
};
