<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Catalog;
use App\Models\Setting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function site()
    {
        $settings = Setting::all();

        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting->key] = json_decode($setting->value, true);
        }

        return response()->json(['site' => $formattedSettings]);
    }

    public function index()
    {
        $orders = Order::with(['items', 'expenses', 'terms'])->orderBy('id', 'desc')->get();
        $catalogs = Catalog::with('items')->get();
        $settings = Setting::all();

        // Format data to match frontend requirements
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
                'taxPercent' => (float) $order->tax_percent,
                'dpPercent' => (float) $order->dp_percent,
                'notes' => $order->notes,
                'items' => $order->items->map(function ($item) {
                    return [
                        'cat' => $item->category,
                        'vendor' => $item->vendor,
                        'desc' => $item->description,
                        'qty' => $item->qty,
                        'cost' => (float) $item->cost,
                        'price' => (float) $item->price,
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
            'site' => $formattedSettings
        ]);
    }

    public function store(Request $request)
    {
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
            'taxPercent' => 'nullable|numeric',
            'dpPercent' => 'nullable|numeric',
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
            'tax_percent' => $data['taxPercent'] ?? 0,
            'dp_percent' => $data['dpPercent'] ?? 0,
            'notes' => $data['notes'] ?? ''
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'category' => $item['cat'] ?? 'Lainnya',
                    'vendor' => $item['vendor'] ?? null,
                    'description' => $item['desc'] ?? '',
                    'qty' => $item['qty'] ?? 0,
                    'cost' => $item['cost'] ?? 0,
                    'price' => $item['price'] ?? 0,
                ]);
            }
        }

        if (!empty($data['expenses'])) {
            foreach ($data['expenses'] as $exp) {
                $order->expenses()->create([
                    'label' => $exp['label'] ?? '',
                    'amount' => $exp['amount'] ?? 0,
                ]);
            }
        }

        if (!empty($data['terms'])) {
            foreach ($data['terms'] as $term) {
                $order->terms()->create([
                    'label' => $term['label'] ?? '',
                    'percent' => $term['percent'] ?? 0,
                    'due_date' => $term['due'] ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'Order created successfully'], 201);
    }

    /**
     * Update order (full: header fields, items, expenses, terms)
     */
    public function update(Request $request, $invoice_no)
    {
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
            'taxPercent'       => 'nullable|numeric',
            'dpPercent'        => 'nullable|numeric',
            'notes'            => 'nullable|string',
            'items'            => 'nullable|array',
            'items.*.cat'      => 'nullable|string',
            'items.*.vendor'   => 'nullable|string',
            'items.*.desc'     => 'nullable|string',
            'items.*.qty'      => 'nullable|numeric',
            'items.*.cost'     => 'nullable|numeric',
            'items.*.price'    => 'nullable|numeric',
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
            'tax_percent' => $data['taxPercent'] ?? $order->tax_percent,
            'dp_percent'  => $data['dpPercent'] ?? $order->dp_percent,
            'notes'       => $data['notes'] ?? $order->notes,
        ]);

        if (array_key_exists('items', $data)) {
            $order->items()->delete();
            foreach (($data['items'] ?? []) as $item) {
                $order->items()->create([
                    'category'    => $item['cat'] ?? 'Lainnya',
                    'vendor'      => $item['vendor'] ?? null,
                    'description' => $item['desc'] ?? '',
                    'qty'         => $item['qty'] ?? 0,
                    'cost'        => $item['cost'] ?? 0,
                    'price'       => $item['price'] ?? 0,
                ]);
            }
        }

        if (array_key_exists('expenses', $data)) {
            $order->expenses()->delete();
            foreach (($data['expenses'] ?? []) as $exp) {
                $order->expenses()->create([
                    'label'  => $exp['label'] ?? '',
                    'amount' => $exp['amount'] ?? 0,
                ]);
            }
        }

        if (array_key_exists('terms', $data)) {
            $order->terms()->delete();
            foreach (($data['terms'] ?? []) as $term) {
                $order->terms()->create([
                    'label'    => $term['label'] ?? '',
                    'percent'  => $term['percent'] ?? 0,
                    'due_date' => $term['due'] ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'Order updated successfully']);
    }

    /**
     * Delete order and all related data
     */
    public function destroy($invoice_no)
    {
        $order = Order::where('invoice_no', $invoice_no)->firstOrFail();
        $order->items()->delete();
        $order->expenses()->delete();
        $order->terms()->delete();
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }

    /**
     * Update site settings (waNumber, email, address, tagline, stats, clients)
     */
    public function updateSettings(Request $request)
    {
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
                ['value' => json_encode($value)]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }

    /**
     * Update catalog (categories + vendors) — full replace
     */
    public function updateCatalog(Request $request)
    {
        $data = $request->validate([
            'catalog'          => 'required|array',
            'catalog.*.cat'    => 'required|string',
            'catalog.*.items'  => 'nullable|array',
        ]);

        // Delete all existing catalogs (cascade deletes items via FK)
        \App\Models\CatalogItem::query()->delete();
        \App\Models\Catalog::query()->delete();

        foreach ($data['catalog'] as $entry) {
            $cat = \App\Models\Catalog::create(['name' => $entry['cat']]);
            foreach (($entry['items'] ?? []) as $itemName) {
                if (trim($itemName)) {
                    $cat->items()->create(['name' => trim($itemName)]);
                }
            }
        }

        return response()->json(['message' => 'Catalog saved successfully']);
    }
}
