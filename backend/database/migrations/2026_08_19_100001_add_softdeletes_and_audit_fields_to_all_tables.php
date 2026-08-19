<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'orders',
            'order_items',
            'order_expenses',
            'order_terms',
            'catalogs',
            'catalog_items',
            'settings',
            'testimonials',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $bl) {
                // Add created_by and updated_by at the end
                $bl->unsignedBigInteger('created_by')->nullable();
                $bl->unsignedBigInteger('updated_by')->nullable();

                // Add soft delete columns at the very end
                $bl->softDeletes();       // adds deleted_at
                $bl->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'orders',
            'order_items',
            'order_expenses',
            'order_terms',
            'catalogs',
            'catalog_items',
            'settings',
            'testimonials',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $bl) {
                // Reverse order: drop deleted_by first, then softDeletes
                $bl->dropColumn('deleted_by');
                $bl->dropSoftDeletes();
                // Then drop created_by + updated_by from end
                $bl->dropColumn(['created_by', 'updated_by']);
            });
        }
    }
};