<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert enum to varchar so new status labels fit, then normalize legacy values
        DB::statement("ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Belum Lunas'");

        DB::table('orders')->where('status', 'DP')->update(['status' => 'Down Payment']);
        DB::table('orders')->where('status', 'Pending')->update(['status' => 'Belum Lunas']);
        DB::table('orders')
            ->whereNotIn('status', ['Belum Lunas', 'Down Payment', 'Lunas'])
            ->update(['status' => 'Belum Lunas']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'Down Payment')->update(['status' => 'DP']);
        DB::table('orders')->where('status', 'Belum Lunas')->update(['status' => 'Pending']);

        DB::statement("ALTER TABLE orders MODIFY status ENUM('Pending','DP','Lunas') NOT NULL DEFAULT 'Pending'");
    }
};
