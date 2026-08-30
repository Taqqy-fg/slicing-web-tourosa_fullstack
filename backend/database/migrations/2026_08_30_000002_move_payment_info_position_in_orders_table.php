<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pindahkan kolom payment_info ke posisi sebelum notes pada tabel orders.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE orders MODIFY payment_info TEXT NULL AFTER tenggat_date');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE orders MODIFY payment_info TEXT NULL');
    }
};
