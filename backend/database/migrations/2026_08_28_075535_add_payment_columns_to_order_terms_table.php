<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_terms', function (Blueprint $table) {
            $table->boolean('is_paid')->default(false)->after('due_date');
            $table->decimal('paid_amount', 18, 2)->default(0)->after('is_paid');
            $table->date('paid_at')->nullable()->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_terms', function (Blueprint $table) {
            $table->dropColumn(['is_paid', 'paid_amount', 'paid_at']);
        });
    }
};
