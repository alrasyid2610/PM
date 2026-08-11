<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Master data untuk field Entitas, Kepemilikan, Kategori Bisnis, dan
 * Sub Kategori Bisnis pada Business Relation — sebelumnya hardcode di
 * frontend (create.blade.php & form.js). Sub Kategori Bisnis hirarkinya
 * di bawah Kategori Bisnis (id_kategori_bisnis).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Kolom string di-collate utf8mb4_general_ci secara eksplisit, samakan
        // dengan tabel lama (business_relations dkk, lihat struktur_local.sql).
        // Default Laravel (utf8mb4_unicode_ci) akan bentrok saat JOIN dengan
        // kolom lama pada migration berikutnya ("Illegal mix of collations").
        Schema::create('entitas', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_entitas');
            $table->string('nama', 100)->unique();
            $table->boolean('is_aktif')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kepemilikan', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_kepemilikan');
            $table->string('nama', 100)->unique();
            $table->boolean('is_aktif')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kategori_bisnis', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_kategori_bisnis');
            $table->string('nama', 150)->unique();
            $table->boolean('is_aktif')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sub_kategori_bisnis', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->increments('id_sub_kategori_bisnis');
            $table->unsignedInteger('id_kategori_bisnis');
            $table->string('nama', 150);
            $table->boolean('is_aktif')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();

        DB::table('entitas')->insert(collect([
            'Perseroan Terbatas', 'Commanditaire Vennootschap', 'Firma', 'Koperasi',
        ])->map(fn($nama) => ['nama' => $nama, 'created_at' => $now, 'updated_at' => $now])->all());

        DB::table('kepemilikan')->insert(collect([
            'Swasta', 'BUMN/BUMD', 'Pemerintah',
        ])->map(fn($nama) => ['nama' => $nama, 'created_at' => $now, 'updated_at' => $now])->all());

        DB::table('kategori_bisnis')->insert(collect([
            'Manufaktur', 'Makanan & Minuman', 'Otomotif', 'Industri', 'Perdagangan', 'Jasa', 'Konstruksi',
        ])->map(fn($nama) => ['nama' => $nama, 'created_at' => $now, 'updated_at' => $now])->all());

        $idOtomotif = DB::table('kategori_bisnis')->where('nama', 'Otomotif')->value('id_kategori_bisnis');
        $idMakmin   = DB::table('kategori_bisnis')->where('nama', 'Makanan & Minuman')->value('id_kategori_bisnis');
        $idIndustri = DB::table('kategori_bisnis')->where('nama', 'Industri')->value('id_kategori_bisnis');

        DB::table('sub_kategori_bisnis')->insert([
            ['id_kategori_bisnis' => $idOtomotif, 'nama' => 'Otomotif', 'created_at' => $now, 'updated_at' => $now],
            ['id_kategori_bisnis' => $idMakmin,   'nama' => 'Food',     'created_at' => $now, 'updated_at' => $now],
            ['id_kategori_bisnis' => $idIndustri, 'nama' => 'Industry', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_kategori_bisnis');
        Schema::dropIfExists('kategori_bisnis');
        Schema::dropIfExists('kepemilikan');
        Schema::dropIfExists('entitas');
    }
};
