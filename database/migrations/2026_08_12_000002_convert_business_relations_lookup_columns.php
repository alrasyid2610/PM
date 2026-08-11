<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Konversi business_relations.entitas/kepemilikan/kategori_bisnis/
 * sub_kategori_bisnis dari varchar (teks bebas) menjadi FK int ke tabel
 * master baru (entitas, kepemilikan, kategori_bisnis, sub_kategori_bisnis).
 *
 * Data lama di-backfill otomatis dengan mencocokkan teks yang tersimpan ke
 * nama master (sesuai 4 pilihan hardcode lama, lihat migration
 * 2026_08_12_000001). Baris yang teksnya TIDAK cocok persis dengan salah
 * satu pilihan hardcode akan menghasilkan id_* NULL setelah migration ini
 * — perlu dicek manual & diperbaiki lewat form Business Relation kalau ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_relations', function (Blueprint $table) {
            $table->unsignedInteger('id_entitas')->nullable()->after('entitas');
            $table->unsignedInteger('id_kepemilikan')->nullable()->after('kepemilikan');
            $table->unsignedInteger('id_kategori_bisnis')->nullable()->after('kategori_bisnis');
            $table->unsignedInteger('id_sub_kategori_bisnis')->nullable()->after('sub_kategori_bisnis');
        });

        DB::statement('
            UPDATE business_relations br
            JOIN entitas e ON e.nama = br.entitas
            SET br.id_entitas = e.id_entitas
        ');
        DB::statement('
            UPDATE business_relations br
            JOIN kepemilikan k ON k.nama = br.kepemilikan
            SET br.id_kepemilikan = k.id_kepemilikan
        ');
        DB::statement('
            UPDATE business_relations br
            JOIN kategori_bisnis kb ON kb.nama = br.kategori_bisnis
            SET br.id_kategori_bisnis = kb.id_kategori_bisnis
        ');
        DB::statement('
            UPDATE business_relations br
            JOIN sub_kategori_bisnis skb ON skb.nama = br.sub_kategori_bisnis
            SET br.id_sub_kategori_bisnis = skb.id_sub_kategori_bisnis
        ');

        Schema::table('business_relations', function (Blueprint $table) {
            $table->dropColumn(['entitas', 'kepemilikan', 'kategori_bisnis', 'sub_kategori_bisnis']);
        });
    }

    public function down(): void
    {
        Schema::table('business_relations', function (Blueprint $table) {
            $table->string('entitas', 100)->nullable()->after('id_entitas');
            $table->string('kepemilikan', 100)->nullable()->after('id_kepemilikan');
            $table->string('kategori_bisnis', 150)->nullable()->after('id_kategori_bisnis');
            $table->string('sub_kategori_bisnis', 150)->nullable()->after('id_sub_kategori_bisnis');
        });

        DB::statement('
            UPDATE business_relations br
            JOIN entitas e ON e.id_entitas = br.id_entitas
            SET br.entitas = e.nama
        ');
        DB::statement('
            UPDATE business_relations br
            JOIN kepemilikan k ON k.id_kepemilikan = br.id_kepemilikan
            SET br.kepemilikan = k.nama
        ');
        DB::statement('
            UPDATE business_relations br
            JOIN kategori_bisnis kb ON kb.id_kategori_bisnis = br.id_kategori_bisnis
            SET br.kategori_bisnis = kb.nama
        ');
        DB::statement('
            UPDATE business_relations br
            JOIN sub_kategori_bisnis skb ON skb.id_sub_kategori_bisnis = br.id_sub_kategori_bisnis
            SET br.sub_kategori_bisnis = skb.nama
        ');

        Schema::table('business_relations', function (Blueprint $table) {
            $table->dropColumn(['id_entitas', 'id_kepemilikan', 'id_kategori_bisnis', 'id_sub_kategori_bisnis']);
        });
    }
};
