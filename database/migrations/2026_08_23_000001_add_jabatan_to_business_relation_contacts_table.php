<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_relation_contacts', function (Blueprint $table) {
            $table->string('jabatan', 100)->nullable()->after('nama_pic');
        });
    }

    public function down(): void
    {
        Schema::table('business_relation_contacts', function (Blueprint $table) {
            $table->dropColumn('jabatan');
        });
    }
};
