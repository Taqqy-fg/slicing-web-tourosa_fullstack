<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_info_id')->constrained('order_infos')->onDelete('cascade');
            $table->string('invoice_no')->unique();
            $table->date('invoice_date');
            $table->string('destination')->nullable();
            $table->date('depart_date')->nullable();
            $table->date('return_date')->nullable();
            $table->integer('pax')->default(0);
            $table->enum('status', ['Pending', 'DP', 'Lunas'])->default('Pending');
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('dp_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
