<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_create_sales_orders_table.php
public function up(): void
{
    Schema::create('sales_orders', function (Blueprint $table) {
        $table->id();
        $table->string('nomor_so')->unique();
        $table->foreignId('kantor_id')->constrained('kantors');
        $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'completed']);
        $table->decimal('total', 15, 2)->default(0);
        $table->date('tanggal');
        $table->timestamps();
    });
}
};
