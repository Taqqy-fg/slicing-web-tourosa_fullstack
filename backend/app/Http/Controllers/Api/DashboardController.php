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
}
