<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Bank Account" (rekening bank) + permission menu 'bank-accounts'
 * (di-seed dari 'satuan' — master data sejenis di grup Referensi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->increments('id_bank_account');
            $table->string('no_rekening', 50);
            $table->string('atas_nama', 150);
            $table->string('bank', 100);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ([
            ['menu_group_permissions', 'menu_group_id'],
            ['user_menu_permissions', 'user_id'],
        ] as [$tbl, $col]) {
            DB::table($tbl)
                ->select($col, 'can_read', 'can_create', 'can_update', 'can_delete')
                ->where('menu_slug', 'satuan')
                ->get()
                ->each(function ($row) use ($tbl, $col) {
                    DB::table($tbl)->updateOrInsert(
                        [$col => $row->$col, 'menu_slug' => 'bank-accounts'],
                        [
                            'can_read'   => $row->can_read,
                            'can_create' => $row->can_create,
                            'can_update' => $row->can_update,
                            'can_delete' => $row->can_delete,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'bank-accounts')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'bank-accounts')->delete();
        Schema::dropIfExists('bank_accounts');
    }
};
