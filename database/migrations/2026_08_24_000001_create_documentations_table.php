<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Documentation — blog-style, per modul aplikasi + tag bebas.
 * Hanya user yang dikasih permission can_create/update/delete di slug
 * "documentations" (Super Admin) yang bisa tulis; can_read bisa dikasih
 * ke semua user supaya bisa baca lewat ikon Help di navbar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentations', function (Blueprint $table) {
            $table->increments('id_documentation');
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('modul'); // label modul aplikasi, lihat config/menus.php
            $table->longText('konten'); // HTML dari Quill editor
            $table->string('ringkasan')->nullable();
            $table->boolean('is_published')->default(1);
            $table->unsignedBigInteger('created_by')->nullable(); // -> users.id, query-level
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('documentation_tags', function (Blueprint $table) {
            $table->increments('id_tag');
            $table->string('nama')->unique();
            $table->timestamps();
        });

        Schema::create('documentation_tag_pivot', function (Blueprint $table) {
            $table->unsignedInteger('id_documentation');
            $table->unsignedInteger('id_tag');
            $table->primary(['id_documentation', 'id_tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentation_tag_pivot');
        Schema::dropIfExists('documentation_tags');
        Schema::dropIfExists('documentations');
    }
};
