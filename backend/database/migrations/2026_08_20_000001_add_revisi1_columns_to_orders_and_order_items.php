<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('discount_type', 2)->default('Rp')->after('discount');
            $table->decimal('service_fee', 15, 2)->default(0)->after('discount_type');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('trip_type', 20)->nullable()->after('vendor');
            $table->string('destination')->nullable()->after('trip_type');
            $table->date('depart_date')->nullable()->after('destination');
            $table->date('return_date')->nullable()->after('depart_date');
            $table->decimal('markup_cost', 15, 2)->default(0)->after('cost');
            $table->decimal('markup_price', 15, 2)->default(0)->after('markup_cost');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'service_fee']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['trip_type', 'destination', 'depart_date', 'return_date', 'markup_cost', 'markup_price']);
        });
    }
};
