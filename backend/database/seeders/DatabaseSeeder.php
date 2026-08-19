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
        // SEED USER
        $admin = User::firstOrCreate(
            ['email' => 'admin@tourosa.id'],
            [
                'name' => 'Admin Tourosa',
                'password' => Hash::make('password'),
            ]
        );
        $admin->is_superadmin = true;
        $admin->save();

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

        // SEED ORDERS
        $ordersData = [
            [
                'no' => 'INV/TRS/2026/0007', 'date' => '2026-06-22', 'group' => 'PT Sinar Abadi — Annual Gathering', 'pic' => 'Bpk. Rendra (HRD)', 'contact' => '0812-3344-5566', 'dest' => 'Bali (Denpasar)', 'depart' => '2026-08-12', 'ret' => '2026-08-15', 'pax' => 45, 'status' => 'Lunas',
                'items' => [
                    ['cat' => 'Tiket Pesawat', 'desc' => 'PP Jakarta–Denpasar (Garuda Indonesia)', 'qty' => 45, 'cost' => 1980000, 'price' => 2150000],
                    ['cat' => 'Hotel', 'desc' => 'The Anvaya Resort — 3 malam, twin share', 'qty' => 45, 'cost' => 1650000, 'price' => 1850000],
                    ['cat' => 'Group Tour / Land Tour', 'desc' => 'Full day tour + tiket masuk objek wisata', 'qty' => 45, 'cost' => 520000, 'price' => 650000],
                    ['cat' => 'Konsumsi', 'desc' => 'Gala dinner & makan harian (3 hari)', 'qty' => 45, 'cost' => 400000, 'price' => 480000],
                    ['cat' => 'Transport', 'desc' => 'Bus pariwisata + BBM & driver (3 hari)', 'qty' => 3, 'cost' => 2900000, 'price' => 3500000],
                ],
                'expenses' => [
                    ['label' => 'Fee koordinator lapangan', 'amount' => 3500000],
                    ['label' => 'Dokumentasi & merchandise', 'amount' => 2750000]
                ],
                'discount' => 2000000, 'taxPercent' => 11, 'dpPercent' => 100,
                'notes' => 'Lunas. Terima kasih atas kepercayaan PT Sinar Abadi.'
            ],
            [
                'no' => 'INV/TRS/2026/0006', 'date' => '2026-06-19', 'group' => 'Komunitas Pendaki Nusantara', 'pic' => 'Sdri. Maya Anggraini', 'contact' => '0813-9988-7766', 'dest' => 'Lombok — Rinjani', 'depart' => '2026-07-20', 'ret' => '2026-07-24', 'pax' => 28, 'status' => 'DP',
                'items' => [
                    ['cat' => 'Tiket Pesawat', 'desc' => 'PP Jakarta–Lombok (Lion Air)', 'qty' => 28, 'cost' => 1320000, 'price' => 1450000],
                    ['cat' => 'Hotel', 'desc' => 'Homestay pre/post trekking — 2 malam', 'qty' => 28, 'cost' => 330000, 'price' => 420000],
                    ['cat' => 'Tour Leader', 'desc' => 'Guide & porter paket Rinjani 3D2N', 'qty' => 28, 'cost' => 1600000, 'price' => 1850000],
                    ['cat' => 'Konsumsi', 'desc' => 'Logistik makan selama trekking', 'qty' => 28, 'cost' => 460000, 'price' => 550000],
                    ['cat' => 'Transport', 'desc' => 'Sewa elf bandara–basecamp PP', 'qty' => 3, 'cost' => 980000, 'price' => 1200000],
                ],
                'expenses' => [
                    ['label' => 'P3K & asuransi trekking', 'amount' => 1200000]
                ],
                'terms' => [
                    ['label' => 'DP Booking', 'percent' => 50, 'due' => '2026-07-06'],
                    ['label' => 'Pelunasan', 'percent' => 50, 'due' => '2026-07-15']
                ],
                'discount' => 0, 'taxPercent' => 11, 'dpPercent' => 50,
                'notes' => 'DP 50% diterima. Pelunasan H-14 sebelum keberangkatan.'
            ],
            [
                'no' => 'INV/TRS/2026/0005', 'date' => '2026-06-15', 'group' => 'Reuni SMA Negeri 5 — Angkatan 2010', 'pic' => 'Bpk. Adi Nugroho', 'contact' => '0817-2211-3344', 'dest' => 'Yogyakarta', 'depart' => '2026-09-05', 'ret' => '2026-09-07', 'pax' => 60, 'status' => 'DP',
                'items' => [
                    ['cat' => 'Hotel', 'desc' => 'Hotel bintang 4 kawasan Malioboro — 2 malam', 'qty' => 60, 'cost' => 660000, 'price' => 780000],
                    ['cat' => 'Group Tour / Land Tour', 'desc' => 'City tour + Borobudur & Prambanan', 'qty' => 60, 'cost' => 430000, 'price' => 540000],
                    ['cat' => 'Konsumsi', 'desc' => 'Gala dinner reuni + makan harian', 'qty' => 60, 'cost' => 350000, 'price' => 420000],
                    ['cat' => 'Transport', 'desc' => 'Bus pariwisata 2 unit (2 hari)', 'qty' => 4, 'cost' => 2350000, 'price' => 2800000],
                ],
                'expenses' => [
                    ['label' => 'Sewa sound system & panggung', 'amount' => 4500000]
                ],
                'discount' => 1500000, 'taxPercent' => 11, 'dpPercent' => 40,
                'notes' => 'DP 40% diterima. Pelunasan H-10 sebelum acara.'
            ],
            [
                'no' => 'INV/TRS/2026/0004', 'date' => '2026-06-08', 'group' => 'Family Trip Keluarga Wijaya', 'pic' => 'Ibu Surya Wijaya', 'contact' => '0811-5566-7788', 'dest' => 'Singapore', 'depart' => '2026-07-02', 'ret' => '2026-07-05', 'pax' => 12, 'status' => 'Lunas',
                'items' => [
                    ['cat' => 'Tiket Pesawat', 'desc' => 'PP Jakarta–Singapore (Singapore Airlines)', 'qty' => 12, 'cost' => 3020000, 'price' => 3250000],
                    ['cat' => 'Hotel', 'desc' => 'Hotel kawasan Orchard — 3 malam', 'qty' => 12, 'cost' => 1880000, 'price' => 2100000],
                    ['cat' => 'Group Tour / Land Tour', 'desc' => 'Universal Studios + Gardens by the Bay', 'qty' => 12, 'cost' => 1180000, 'price' => 1450000],
                    ['cat' => 'Dokumen / Visa', 'desc' => 'Pengurusan dokumen perjalanan', 'qty' => 12, 'cost' => 90000, 'price' => 150000],
                ],
                'expenses' => [],
                'discount' => 0, 'taxPercent' => 11, 'dpPercent' => 100,
                'notes' => 'Lunas. Selamat menikmati perjalanan keluarga.'
            ]
        ];

        foreach ($ordersData as $data) {
            $order = Order::create([
                'invoice_no' => $data['no'],
                'invoice_date' => $data['date'],
                'group_name' => $data['group'],
                'pic_name' => $data['pic'],
                'contact_info' => $data['contact'],
                'destination' => $data['dest'],
                'depart_date' => $data['depart'],
                'return_date' => $data['ret'],
                'pax' => (int) $data['pax'],
                'status' => $data['status'],
                'discount' => $data['discount'],
                'tax_percent' => $data['taxPercent'],
                'dp_percent' => $data['dpPercent'],
                'notes' => $data['notes']
            ]);

            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'category' => $item['cat'],
                        'description' => $item['desc'],
                        'qty' => $item['qty'],
                        'cost' => $item['cost'],
                        'price' => $item['price']
                    ]);
                }
            }

            if (isset($data['expenses'])) {
                foreach ($data['expenses'] as $exp) {
                    OrderExpense::create([
                        'order_id' => $order->id,
                        'label' => $exp['label'],
                        'amount' => $exp['amount']
                    ]);
                }
            }

            if (isset($data['terms'])) {
                foreach ($data['terms'] as $term) {
                    OrderTerm::create([
                        'order_id' => $order->id,
                        'label' => $term['label'],
                        'percent' => $term['percent'],
                        'due_date' => $term['due']
                    ]);
                }
            }
        }

        // SEED TESTIMONIALS
        Testimonial::firstOrCreate(
            ['quote' => 'Outing kantor 120 orang ke Bali berjalan mulus tanpa drama. Penawaran jelas, invoice rapi untuk reimbursement, dan tim Tourosa standby penuh di lokasi.'],
            [
                'name' => 'Rendra Pratama',
                'role' => 'HRD',
                'company' => 'PT Sinar Abadi',
                'avatar_path' => null,
                'sort_order' => 0,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => '2026-08-19 07:31:39',
                'updated_at' => '2026-08-19 07:31:39',
            ]
        );
    }
}
