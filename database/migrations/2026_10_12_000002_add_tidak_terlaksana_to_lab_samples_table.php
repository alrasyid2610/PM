<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Titik sample FWO yang tidak bisa diambil: status baru `tidak_terlaksana` +
 * kolom alasan (master `alasan_tidak_terlaksana`). Kolom `status` dibuat manual
 * sebelum ada migration, jadi definisinya dibaca dulu (SHOW COLUMNS) lalu hanya
 * nilai enum ditambah — nullability & default dipertahankan apa adanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $col = DB::selectOne("SHOW COLUMNS FROM lab_samples WHERE Field = 'status'");

        if ($col && str_starts_with(strtolower($col->Type), 'enum(') && !str_contains($col->Type, 'tidak_terlaksana')) {
            $newType = preg_replace('/\)$/', ",'tidak_terlaksana')", $col->Type);
            $null    = $col->Null === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $col->Default !== null
                ? "DEFAULT '" . str_replace("'", "''", $col->Default) . "'"
                : ($col->Null === 'YES' ? 'DEFAULT NULL' : '');

            DB::statement("ALTER TABLE lab_samples MODIFY `status` {$newType} {$null} {$default}");
        }

        Schema::table('lab_samples', function (Blueprint $table) {
            $table->unsignedInteger('id_alasan_tidak_terlaksana')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        DB::table('lab_samples')->where('status', 'tidak_terlaksana')->update(['status' => 'belum_diambil']);

        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropColumn('id_alasan_tidak_terlaksana');
        });

        $col = DB::selectOne("SHOW COLUMNS FROM lab_samples WHERE Field = 'status'");
        if ($col && str_contains($col->Type, 'tidak_terlaksana')) {
            $oldType = str_replace(",'tidak_terlaksana'", '', $col->Type);
            $null    = $col->Null === 'YES' ? 'NULL' : 'NOT NULL';
            $default = $col->Default !== null
                ? "DEFAULT '" . str_replace("'", "''", $col->Default) . "'"
                : ($col->Null === 'YES' ? 'DEFAULT NULL' : '');
            DB::statement("ALTER TABLE lab_samples MODIFY `status` {$oldType} {$null} {$default}");
        }
    }
};
