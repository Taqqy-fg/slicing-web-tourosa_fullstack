<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
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

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $bl) {
                // deleted_at nempel setelah updated_at
                $bl->softDeletes()->after('updated_at');

                // lalu created_by, updated_by, deleted_by berurutan
                $bl->unsignedBigInteger('created_by')->nullable()->after('deleted_at');
                $bl->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $bl->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $bl) {
                // Reverse: drop dari yang paling terakhir ditambahkan
                $bl->dropColumn(['deleted_by', 'updated_by', 'created_by']);
                $bl->dropSoftDeletes();
            });
        }
    }
};