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
                // Add at end: created_by, updated_by
                $bl->unsignedBigInteger('created_by')->nullable();
                $bl->unsignedBigInteger('updated_by')->nullable();

                // softDeletes() adds deleted_at + deleted_by at the end
                $bl->softDeletes();
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
                // Reverse order: first drop softDeletes (deleted_at + deleted_by)
                $bl->dropSoftDeletes();
                // Then drop created_by + updated_by from end
                $bl->dropColumn(['created_by', 'updated_by']);
            });
        }
    }
};