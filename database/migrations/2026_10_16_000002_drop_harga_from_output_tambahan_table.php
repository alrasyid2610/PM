<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('output_tambahan', 'harga')) {
            Schema::table('output_tambahan', function (Blueprint $table) {
                $table->dropColumn('harga');
            });
        }
    }

    public function down(): void
    {
        Schema::table('output_tambahan', function (Blueprint $table) {
            $table->integer('harga')->default(0)->after('id_satuan');
        });
    }
};
