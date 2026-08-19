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
            'site' => $formattedSettings,
            'testimonials' => $testimonials
        ]);
    }

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
            'notes' => $data['notes'] ?? '',
            'created_by' => $userId,
            'updated_by' => $userId,
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
                    'description' => $item['desc'] ?? '',
                    'qty'         => $item['qty'] ?? 0,
                    'cost'        => $item['cost'] ?? 0,
                    'price'       => $item['price'] ?? 0,
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
