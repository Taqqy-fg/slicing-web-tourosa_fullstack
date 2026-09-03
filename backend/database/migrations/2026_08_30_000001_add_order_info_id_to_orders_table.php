<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel `orders` (desain lama) belum punya kolom order_info_id,
     * karena migration 2026_08_14_035402_create_orders_table.php diedit
     * setelah pernah dijalankan. Migration ini menambahkan kolom
     * order_info_id dan mengisi ulang data order yang ada ke tabel
     * order_infos agar sesuai dengan desain kode sekarang.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'order_info_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('order_info_id')->nullable()->after('id')->constrained('order_infos')->onDelete('cascade');
            });
        }

        // Backfill: buat baris order_infos dari data flat di tabel orders
        // (group_name, pic_name, contact_info) bila belum ada.
        if (Schema::hasColumn('orders', 'group_name')) {
            $orders = DB::table('orders')->whereNull('order_info_id')->get([
                'id', 'group_name', 'pic_name', 'created_by', 'updated_by',
            ]);

            foreach ($orders as $order) {
                $infoId = DB::table('order_infos')->insertGetId([
                    'group_name'   => $order->group_name ?: 'Tanpa Nama Grup',
                    'pic_name'     => $order->pic_name ?? '',
                    'contact_info' => $order->contact_info ?? '',
                    'email'        => '',
                    'address'      => null,
                    'notes'        => null,
                    'created_by'   => $order->created_by,
                    'updated_by'   => $order->updated_by,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                DB::table('orders')
                    ->where('id', $order->id)
                    ->update(['order_info_id' => $infoId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_info_id');
        });
    }
};
