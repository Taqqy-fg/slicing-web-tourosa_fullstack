<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderExpense;
use App\Models\OrderTerm;
use App\Models\Catalog;
use App\Models\CatalogItem;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // SEED ROLES & PERMISSIONS first
        $this->call(RolePermissionSeeder::class);

        // SEED SETTINGS
        Setting::insert([
            ['key' => 'waNumber', 'value' => json_encode('6281200000000')],
            ['key' => 'email', 'value' => json_encode('halo@tourosa.id')],
            ['key' => 'address', 'value' => json_encode('Jakarta, Indonesia')],
            ['key' => 'tagline', 'value' => json_encode('Tiket pesawat, hotel, group tour, hingga gathering korporat — Tourosa mengurus semuanya, lengkap dengan penawaran transparan dan invoice resmi.')],
            ['key' => 'stats', 'value' => json_encode([['n' => '12+', 'l' => 'Tahun pengalaman'], ['n' => '800+', 'l' => 'Grup diberangkatkan'], ['n' => '50+', 'l' => 'Destinasi']])],
            ['key' => 'clients', 'value' => json_encode([
                ['name' => 'KAI', 'img' => null], ['name' => 'Pertamina', 'img' => null], ['name' => 'Bank Mandiri', 'img' => null],
                ['name' => 'Telkom', 'img' => null], ['name' => 'BRI', 'img' => null], ['name' => 'Garuda Indonesia', 'img' => null]
            ])]
        ]);

        // SEED CATALOGS
        $catalogsData = [
            ['cat' => 'Tiket Pesawat', 'items' => ['Garuda Indonesia', 'Batik Air', 'Citilink', 'Lion Air', 'AirAsia', 'NAM Air', 'Super Air Jet']],
            ['cat' => 'Hotel', 'items' => ['The Anvaya Resort', 'Hotel Santika', 'Aston Hotel', 'Swiss-Belhotel', 'Grand Mercure', 'Harris Hotel']],
            ['cat' => 'Group Tour / Land Tour', 'items' => ['City Tour', 'Full Day Tour', 'Land Tour', 'Open Trip', 'Private Tour']],
            ['cat' => 'Konsumsi', 'items' => ['Catering Prasmanan', 'Gala Dinner', 'Nasi Box', 'Coffee Break']],
            ['cat' => 'Transport', 'items' => ['Bus Pariwisata', 'Elf / Hiace', 'Sewa Mobil', 'Big Bus']],
            ['cat' => 'Tour Leader', 'items' => ['Tour Leader', 'Local Guide', 'Porter']],
            ['cat' => 'Dokumen / Visa', 'items' => ['Visa', 'Paspor', 'Asuransi Perjalanan']],
            ['cat' => 'Lainnya', 'items' => []]
        ];

        foreach ($catalogsData as $cat) {
            $catalog = Catalog::create(['name' => $cat['cat']]);
            foreach ($cat['items'] as $item) {
                CatalogItem::create(['catalog_id' => $catalog->id, 'name' => $item]);
            }
        }

        // Dummy data for orders and testimonials has been removed.
    }
}
