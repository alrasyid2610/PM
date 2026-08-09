<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * pic_input, pic_order, pic_marketing_internal, pic_marketing_eksternal
     * menyimpan id user (users.id) tapi kolomnya masih varchar(100) — sisa dari
     * sebelum field ini pakai select2. Diubah ke int unsigned agar konsisten
     * dengan tipe FK yang sebenarnya.
     */
    public function up(): void
    {
        $columns = ['pic_input', 'pic_order', 'pic_marketing_internal', 'pic_marketing_eksternal'];

        // Bersihkan dulu nilai lama yang bukan angka murni (misal sisa data lama
        // sebelum field ini pakai select2), supaya ALTER tidak gagal/truncate diam-diam.
        foreach ($columns as $column) {
            DB::statement("UPDATE sales_orders SET {$column} = NULL WHERE {$column} IS NOT NULL AND {$column} NOT REGEXP '^[0-9]+$'");
        }

        foreach ($columns as $column) {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN {$column} INT UNSIGNED NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        $columns = ['pic_input', 'pic_order', 'pic_marketing_internal', 'pic_marketing_eksternal'];

        foreach ($columns as $column) {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN {$column} VARCHAR(100) NULL DEFAULT NULL");
        }
    }
};
