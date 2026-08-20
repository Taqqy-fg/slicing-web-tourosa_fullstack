<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Catalog;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dapatkan data site untuk landing page (public).
     */
    public function site()
    {
        $settings = Setting::all();

        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting->key] = json_decode($setting->value, true);
        }

        $testimonials = Testimonial::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['site' => $formattedSettings, 'testimonials' => $testimonials]);
    }

    /**
     * Dapatkan semua data dashboard (orders, catalog, settings, testimonials).
     */
    public function index()
    {
        $orders = Order::with(['items', 'expenses', 'terms'])->orderBy('id', 'desc')->get();
        $catalogs = Catalog::with('items')->get();
        $settings = Setting::all();
        $testimonials = Testimonial::orderBy('sort_order')->orderBy('id')->get();

        $formattedOrders = $orders->map(function ($order) {
            return [
                'no' => $order->invoice_no,
                'date' => $order->invoice_date,
                'group' => $order->group_name,
                'pic' => $order->pic_name,
                'contact' => $order->contact_info,
                'dest' => $order->destination,
                'depart' => $order->depart_date,
                'ret' => $order->return_date,
                'pax' => $order->pax,
                'status' => $order->status,
                'discount' => (float) $order->discount,
                'discountType' => $order->discount_type ?? 'Rp',
                'serviceFee' => (float) ($order->service_fee ?? 0),
                'serviceFeeType' => $order->service_fee_type ?? 'Rp',
                'taxPercent' => (float) $order->tax_percent,
                'dpPercent' => (float) $order->dp_percent,
                'dpDueDate' => $order->dp_due_date,
                'notes' => $order->notes,
                'items' => $order->items->map(function ($item) {
                    return [
                        'cat' => $item->category,
                        'vendor' => $item->vendor,
                        'tripType' => $item->trip_type,
                        'dest' => $item->destination,
                        'depart' => $item->depart_date,
                        'ret' => $item->return_date,
                        'desc' => $item->description,
                        'qty' => $item->qty,
                        'cost' => (float) $item->cost,
                        'markupCost' => (float) ($item->markup_cost ?? 0),
                        'price' => (float) $item->price,
                        'markupPrice' => (float) ($item->markup_price ?? 0),
                    ];
                }),
                'expenses' => $order->expenses->map(function ($exp) {
                    return [
                        'label' => $exp->label,
                        'amount' => (float) $exp->amount,
                    ];
                }),
                'terms' => $order->terms->map(function ($term) {
                    return [
                        'label' => $term->label,
                        'percent' => (float) $term->percent,
                        'due' => $term->due_date,
                    ];
                })
            ];
        });

        $formattedCatalogs = $catalogs->map(function ($cat) {
            return [
                'cat' => $cat->name,
                'items' => $cat->items->pluck('name')
            ];
        });

        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting->key] = json_decode($setting->value, true);
        }

        return response()->json([
            'orders' => $formattedOrders,
            'catalog' => $formattedCatalogs,
            'site' => $formattedSettings,
            'testimonials' => $testimonials
        ]);
    }

    /**
     * Buat order baru.
     *
     * @bodyParam no string required Nomor invoice. Example: INV-001
     * @bodyParam date string required Tanggal invoice. Example: 2026-08-19
     * @bodyParam status string required Status order. Example: Pending
     */
    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'no' => 'required|string|unique:orders,invoice_no',
            'date' => 'required|date',
            'group' => 'nullable|string',
            'pic' => 'nullable|string',
            'contact' => 'nullable|string',
            'dest' => 'nullable|string',
            'depart' => 'nullable|date',
            'ret' => 'nullable|date',
            'pax' => 'nullable|numeric',
            'status' => 'required|string',
            'discount' => 'nullable|numeric',
            'discountType' => 'nullable|string',
            'serviceFee' => 'nullable|numeric',
            'serviceFeeType' => 'nullable|string',
            'taxPercent' => 'nullable|numeric',
            'dpPercent' => 'nullable|numeric',
            'dpDueDate' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'expenses' => 'nullable|array',
            'terms' => 'nullable|array'
        ]);

        $order = Order::create([
            'invoice_no' => $data['no'],
            'invoice_date' => $data['date'],
            'group_name' => $data['group'] ?? 'Tanpa Nama Grup',
            'pic_name' => $data['pic'] ?? '',
            'contact_info' => $data['contact'] ?? '',
            'destination' => $data['dest'] ?? '-',
            'depart_date' => $data['depart'],
            'return_date' => $data['ret'],
            'pax' => $data['pax'] ?? 0,
            'status' => $data['status'],
            'discount' => $data['discount'] ?? 0,
            'discount_type' => $data['discountType'] ?? 'Rp',
            'service_fee' => $data['serviceFee'] ?? 0,
            'service_fee_type' => $data['serviceFeeType'] ?? 'Rp',
            'tax_percent' => $data['taxPercent'] ?? 0,
            'dp_percent' => $data['dpPercent'] ?? 0,
            'dp_due_date' => $data['dpDueDate'] ?? null,
            'notes' => $data['notes'] ?? '',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'category' => $item['cat'] ?? 'Lainnya',
                    'vendor' => $item['vendor'] ?? null,
                    'trip_type' => $item['tripType'] ?? null,
                    'destination' => $item['dest'] ?? null,
                    'depart_date' => $item['depart'] ?? null,
                    'return_date' => $item['ret'] ?? null,
                    'description' => $item['desc'] ?? '',
                    'qty' => $item['qty'] ?? 0,
                    'cost' => $item['cost'] ?? 0,
                    'markup_cost' => $item['markupCost'] ?? 0,
                    'price' => $item['price'] ?? 0,
                    'markup_price' => $item['markupPrice'] ?? 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        }

        if (!empty($data['expenses'])) {
            foreach ($data['expenses'] as $exp) {
                $order->expenses()->create([
                    'label' => $exp['label'] ?? '',
                    'amount' => $exp['amount'] ?? 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        }

        if (!empty($data['terms'])) {
            foreach ($data['terms'] as $term) {
                $order->terms()->create([
                    'label' => $term['label'] ?? '',
                    'percent' => $term['percent'] ?? 0,
                    'due_date' => $term['due'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        }

        return response()->json(['message' => 'Order created successfully'], 201);
    }

    /**
     * Update order berdasarkan nomor invoice.
     */
    public function update(Request $request, $invoice_no)
    {
        $userId = $request->user()->id;
        $order = Order::with(['items', 'expenses', 'terms'])->where('invoice_no', $invoice_no)->firstOrFail();

        $data = $request->validate([
            'group'            => 'nullable|string',
            'pic'              => 'nullable|string',
            'contact'          => 'nullable|string',
            'dest'             => 'nullable|string',
            'depart'           => 'nullable|date',
            'ret'              => 'nullable|date',
            'pax'              => 'nullable|numeric',
            'status'           => 'nullable|string',
            'discount'         => 'nullable|numeric',
            'discountType'     => 'nullable|string',
            'serviceFee'       => 'nullable|numeric',
            'taxPercent'       => 'nullable|numeric',
            'dpPercent'        => 'nullable|numeric',
            'dpDueDate'        => 'nullable|date',
            'notes'            => 'nullable|string',
            'items'            => 'nullable|array',
            'items.*.cat'      => 'nullable|string',
            'items.*.vendor'   => 'nullable|string',
            'items.*.tripType' => 'nullable|string',
            'items.*.dest'     => 'nullable|string',
            'items.*.depart'   => 'nullable|date',
            'items.*.ret'      => 'nullable|date',
            'items.*.desc'     => 'nullable|string',
            'items.*.qty'      => 'nullable|numeric',
            'items.*.cost'     => 'nullable|numeric',
            'items.*.markupCost'  => 'nullable|numeric',
            'items.*.price'    => 'nullable|numeric',
            'items.*.markupPrice' => 'nullable|numeric',
            'expenses'         => 'nullable|array',
            'expenses.*.label' => 'nullable|string',
            'expenses.*.amount'=> 'nullable|numeric',
            'terms'            => 'nullable|array',
            'terms.*.label'    => 'nullable|string',
            'terms.*.percent'  => 'nullable|numeric',
            'terms.*.due'      => 'nullable|date',
        ]);

        $order->update([
            'group_name'  => $data['group'] ?? $order->group_name,
            'pic_name'    => $data['pic'] ?? $order->pic_name,
            'contact_info'=> $data['contact'] ?? $order->contact_info,
            'destination' => $data['dest'] ?? $order->destination,
            'depart_date' => $data['depart'] ?? $order->depart_date,
            'return_date' => $data['ret'] ?? $order->return_date,
            'pax'         => $data['pax'] ?? $order->pax,
            'status'      => $data['status'] ?? $order->status,
            'discount'    => $data['discount'] ?? $order->discount,
            'discount_type' => $data['discountType'] ?? $order->discount_type,
            'service_fee' => $data['serviceFee'] ?? $order->service_fee,
            'service_fee_type' => $data['serviceFeeType'] ?? $order->service_fee_type,
            'tax_percent' => $data['taxPercent'] ?? $order->tax_percent,
            'dp_percent'  => $data['dpPercent'] ?? $order->dp_percent,
            'dp_due_date' => $data['dpDueDate'] ?? $order->dp_due_date,
            'notes'       => $data['notes'] ?? $order->notes,
            'updated_by'  => $userId,
        ]);

        if (array_key_exists('items', $data)) {
            foreach ($order->items as $item) {
                $item->update(['deleted_by' => $userId]);
                $item->delete();
            }
            foreach (($data['items'] ?? []) as $item) {
                $order->items()->create([
                    'category'    => $item['cat'] ?? 'Lainnya',
                    'vendor'      => $item['vendor'] ?? null,
                    'trip_type'   => $item['tripType'] ?? null,
                    'destination' => $item['dest'] ?? null,
                    'depart_date' => $item['depart'] ?? null,
                    'return_date' => $item['ret'] ?? null,
                    'description' => $item['desc'] ?? '',
                    'qty'         => $item['qty'] ?? 0,
                    'cost'        => $item['cost'] ?? 0,
                    'markup_cost' => $item['markupCost'] ?? 0,
                    'price'       => $item['price'] ?? 0,
                    'markup_price'=> $item['markupPrice'] ?? 0,
                    'created_by'  => $userId,
                    'updated_by'  => $userId,
                ]);
            }
        }

        if (array_key_exists('expenses', $data)) {
            foreach ($order->expenses as $exp) {
                $exp->update(['deleted_by' => $userId]);
                $exp->delete();
            }
            foreach (($data['expenses'] ?? []) as $exp) {
                $order->expenses()->create([
                    'label'      => $exp['label'] ?? '',
                    'amount'     => $exp['amount'] ?? 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        }

        if (array_key_exists('terms', $data)) {
            foreach ($order->terms as $term) {
                $term->update(['deleted_by' => $userId]);
                $term->delete();
            }
            foreach (($data['terms'] ?? []) as $term) {
                $order->terms()->create([
                    'label'      => $term['label'] ?? '',
                    'percent'    => $term['percent'] ?? 0,
                    'due_date'   => $term['due'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }
        }

        return response()->json(['message' => 'Order updated successfully']);
    }

    /**
     * Hapus order berdasarkan nomor invoice.
     */
    public function destroy(Request $request, $invoice_no)
    {
        $userId = $request->user()->id;
        $order = Order::where('invoice_no', $invoice_no)->firstOrFail();

        foreach ($order->items as $item) {
            $item->update(['deleted_by' => $userId]);
            $item->delete();
        }
        foreach ($order->expenses as $exp) {
            $exp->update(['deleted_by' => $userId]);
            $exp->delete();
        }
        foreach ($order->terms as $term) {
            $term->update(['deleted_by' => $userId]);
            $term->delete();
        }

        $order->update(['deleted_by' => $userId]);
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    /**
     * Simpan pengaturan site (waNumber, email, address, tagline, stats, clients).
     */
    public function updateSettings(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'waNumber' => 'nullable|string',
            'email'    => 'nullable|string',
            'address'  => 'nullable|string',
            'tagline'  => 'nullable|string',
            'stats'    => 'nullable|array',
            'clients'  => 'nullable|array',
        ]);

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => json_encode($value),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }

    /**
     * Simpan catalog lengkap (kategori + vendor).
     */
    public function updateCatalog(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'catalog'          => 'required|array',
            'catalog.*.cat'    => 'required|string',
            'catalog.*.items'  => 'nullable|array',
        ]);

        $oldCatalogItems = CatalogItem::all();
        foreach ($oldCatalogItems as $item) {
            $item->update(['deleted_by' => $userId]);
        }
        $oldCatalogs = Catalog::all();
        foreach ($oldCatalogs as $cat) {
            $cat->update(['deleted_by' => $userId]);
        }

        CatalogItem::query()->delete();
        Catalog::query()->delete();

        foreach ($data['catalog'] as $entry) {
            $cat = Catalog::create([
                'name' => $entry['cat'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            foreach (($entry['items'] ?? []) as $itemName) {
                if (trim($itemName)) {
                    $cat->items()->create([
                        'name' => trim($itemName),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Catalog saved successfully']);
    }
}
