<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master Label Budget (sejak 2026-11-19): label Budget Plan tidak lagi diketik bebas, dipilih dari master `budget_label`.
 * Plan menyimpan `id_budget_label` + salinan teks di kolom `label` lama (notifikasi, nama file PDF, riwayat membacanya).
 * Plan lama dibiarkan apa adanya (id kosong = label lama), kecuali teksnya persis sama dengan nama di master.
 */
return new class extends Migration
{
    private const PLAN_TABLES = ['wo_budgets', 'fwo_budgets', 'sq_wo_budgets', 'sq_fwo_budgets'];

    private const SEED = [
        'Bulanan Internal',
        'Bulanan LLHD',
        'Semester',
        'Implementasi',
        'Surat Keterangan (SUKET)',
        'Lingkungan Kerja',
    ];

    public function up(): void
    {
        Schema::create('budget_label', function (Blueprint $table) {
            $table->id('id_budget_label');
            $table->string('nama', 255)->unique();
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        $now = now();
        foreach (self::SEED as $i => $nama) {
            DB::table('budget_label')->insert([
                'nama' => $nama, 'urutan' => $i + 1, 'is_aktif' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach (self::PLAN_TABLES as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->unsignedBigInteger('id_budget_label')->nullable()->after('label');
                $table->index('id_budget_label');
            });

            // Tautkan plan lama yang teksnya persis sama dengan nama master (tidak peka huruf besar-kecil)
            DB::statement("UPDATE `{$tbl}` p JOIN budget_label m ON m.nama = p.label COLLATE utf8mb4_general_ci SET p.id_budget_label = m.id_budget_label");
        }

        // Izin menu baru: salin dari Budget Account (grup & user yang boleh mengelola Budget Account)
        foreach ([['menu_group_permissions', 'menu_group_id'], ['user_menu_permissions', 'user_id']] as [$tbl, $col]) {
            DB::table($tbl)
                ->select($col, 'can_read', 'can_create', 'can_update', 'can_delete')
                ->where('menu_slug', 'budget-accounts')
                ->get()
                ->each(function ($row) use ($tbl, $col) {
                    DB::table($tbl)->updateOrInsert(
                        [$col => $row->$col, 'menu_slug' => 'budget-label'],
                        [
                            'can_read' => $row->can_read, 'can_create' => $row->can_create,
                            'can_update' => $row->can_update, 'can_delete' => $row->can_delete,
                            'created_at' => now(), 'updated_at' => now(),
                        ]
                    );
                });
        }
    }

    public function down(): void
    {
        DB::table('menu_group_permissions')->where('menu_slug', 'budget-label')->delete();
        DB::table('user_menu_permissions')->where('menu_slug', 'budget-label')->delete();

        foreach (self::PLAN_TABLES as $tbl) {
            Schema::table($tbl, function (Blueprint $table) {
                $table->dropIndex(['id_budget_label']);
                $table->dropColumn('id_budget_label');
            });
        }

        Schema::dropIfExists('budget_label');
    }
};
