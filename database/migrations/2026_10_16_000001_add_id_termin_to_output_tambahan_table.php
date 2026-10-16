<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('output_tambahan', function (Blueprint $table) {
            $table->unsignedInteger('id_termin')->nullable()->after('id_wo')->index();
            $table->string('judul_tagihan', 255)->nullable()->after('id_termin');
        });
    }

    public function down(): void
    {
        Schema::table('output_tambahan', function (Blueprint $table) {
            $table->dropIndex(['id_termin']);
            $table->dropColumn(['id_termin', 'judul_tagihan']);
        });
    }
};
