<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Catalog;
use App\Models\CatalogItem;
use App\Models\OrderInfo;
use App\Models\OrderPayment;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $orders = Order::with(['orderInfo', 'items', 'expenses', 'terms', 'payments'])->orderBy('id', 'desc')->get();
        $catalogs = Catalog::with('items')->get();
        $settings = Setting::all();
        $testimonials = Testimonial::orderBy('sort_order')->orderBy('id')->get();
        $customers = \App\Models\Customer::orderBy('name')->get();
        $orderInfos = OrderInfo::orderBy('id', 'desc')->get();

        $formattedOrders = $orders->map(function ($order) {
            return [
                'no' => $order->invoice_no,
                'date' => $order->invoice_date,
                'group' => $order->orderInfo?->group_name,
                'pic' => $order->orderInfo?->pic_name,
                'contact' => $order->orderInfo?->contact_info,
                'email' => $order->orderInfo?->email,
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
                'tenggatDate' => $order->tenggat_date,
                'notes' => $order->notes,
                'payment_info' => $order->payment_info,
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
                        'id' => $term->id,
                        'label' => $term->label,
                        'percent' => (float) $term->percent,
                        'due' => $term->due_date,
                        'is_paid' => (bool) $term->is_paid,
                        'paid_amount' => (float) $term->paid_amount,
                        'paid_at' => $term->paid_at,
                    ];
                }),
                'payments' => $order->payments->map(function ($pay) {
                    return [
                        'id' => $pay->id,
                        'payment_date' => $pay->payment_date,
                        'amount' => (float) $pay->amount,
                        'proof_file' => $pay->proof_file ? Storage::url($pay->proof_file) : null,
                        'comment' => $pay->comment,
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
            'orders'      => $formattedOrders,
            'catalog'     => $formattedCatalogs,
            'site'        => $formattedSettings,
            'testimonials'=> $testimonials,
            'customers'   => $customers,
            'order_infos' => $orderInfos->map(fn ($i) => [
                'id'           => $i->id,
                'group_name'   => $i->group_name,
                'pic_name'     => $i->pic_name,
                'contact_info' => $i->contact_info,
                'email'        => $i->email,
                'address'      => $i->address,
                'notes'        => $i->notes,
                'created_at'   => $i->created_at?->toDateString(),
            ]),
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
            'no' => ['required', 'string', \Illuminate\Validation\Rule::unique('orders', 'invoice_no')->whereNull('deleted_at')],
            'date' => 'required|date',
            'order_info_id' => 'nullable|exists:order_infos,id',
            'group' => 'nullable|string',
            'pic' => 'nullable|string',
            'contact' => 'nullable|string',
            'email' => 'nullable|string|email|max:255',
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
            'tenggatDate' => 'nullable|date',
            'notes' => 'nullable|string',
            'payment_info' => 'nullable|string',
            'items' => 'nullable|array',
            'expenses' => 'nullable|array',
            'terms' => 'nullable|array'
        ]);

        $orderInfoId = $data['order_info_id'] ?? null;
        if (!$orderInfoId) {
            $existingInfo = OrderInfo::where('group_name', $data['group'] ?? 'Tanpa Nama Grup')->orderBy('id', 'desc')->first();
            if ($existingInfo) {
                $orderInfoId = $existingInfo->id;
                // Update pic and contact if they were provided and empty in db
                if ($data['pic'] || $data['contact'] || $data['email']) {
                    $existingInfo->update([
                        'pic_name' => $data['pic'] ?? $existingInfo->pic_name,
                        'contact_info' => $data['contact'] ?? $existingInfo->contact_info,
                        'email' => $data['email'] ?? $existingInfo->email,
                        'updated_by' => $userId,
                    ]);
                }
            } else {
                $orderInfo = OrderInfo::create([
                    'group_name' => $data['group'] ?? 'Tanpa Nama Grup',
                    'pic_name' => $data['pic'] ?? '',
                    'contact_info' => $data['contact'] ?? '',
                    'email' => $data['email'] ?? '',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $orderInfoId = $orderInfo->id;
            }
        }

        $order = Order::create([
            'order_info_id' => $orderInfoId,
            'invoice_no' => $data['no'],
            'invoice_date' => $data['date'],
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
            'tenggat_date' => $data['tenggatDate'] ?? null,
            'notes' => $data['notes'] ?? '',
            'payment_info' => $data['payment_info'] ?? null,
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
            'order_info_id'    => 'nullable|exists:order_infos,id',
            'group'            => 'nullable|string',
            'pic'              => 'nullable|string',
            'contact'          => 'nullable|string',
            'email'            => 'nullable|string|email|max:255',
            'dest'             => 'nullable|string',
            'depart'           => 'nullable|date',
            'ret'              => 'nullable|date',
            'pax'              => 'nullable|numeric',
            'status'           => 'nullable|string',
            'discount'         => 'nullable|numeric',
            'discountType'     => 'nullable|string',
            'serviceFee'       => 'nullable|numeric',
            'serviceFeeType'   => 'nullable|string',
            'taxPercent'       => 'nullable|numeric',
            'dpPercent'        => 'nullable|numeric',
            'dpDueDate'        => 'nullable|date',
            'tenggatDate'      => 'nullable|date',
            'notes'            => 'nullable|string',
            'payment_info'     => 'nullable|string',
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

        $orderInfoId = $data['order_info_id'] ?? $order->order_info_id;
        
        if ($orderInfoId && (isset($data['group']) || isset($data['pic']) || isset($data['contact']))) {
            $orderInfo = OrderInfo::find($orderInfoId);
            if ($orderInfo) {
                $orderInfo->update([
                    'group_name'  => $data['group'] ?? $orderInfo->group_name,
                    'pic_name'    => $data['pic'] ?? $orderInfo->pic_name,
                    'contact_info'=> $data['contact'] ?? $orderInfo->contact_info,
                    'email'       => $data['email'] ?? $orderInfo->email,
                    'updated_by'  => $userId,
                ]);
            }
        }

        $order->update([
            'order_info_id' => $orderInfoId,
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
            'tenggat_date' => $data['tenggatDate'] ?? $order->tenggat_date,
            'notes'       => $data['notes'] ?? $order->notes,
            'payment_info'=> $data['payment_info'] ?? $order->payment_info,
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
            $existingTermIds = [];
            foreach (($data['terms'] ?? []) as $term) {
                if (!empty($term['id'])) {
                    $order->terms()->where('id', $term['id'])->update([
                        'label'      => $term['label'] ?? '',
                        'percent'    => $term['percent'] ?? 0,
                        'due_date'   => $term['due'] ?? null,
                        'updated_by' => $userId,
                    ]);
                    $existingTermIds[] = $term['id'];
                } else {
                    $newTerm = $order->terms()->create([
                        'label'      => $term['label'] ?? '',
                        'percent'    => $term['percent'] ?? 0,
                        'due_date'   => $term['due'] ?? null,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $existingTermIds[] = $newTerm->id;
                }
            }
            foreach ($order->terms as $term) {
                if (!in_array($term->id, $existingTermIds)) {
                    $term->update(['deleted_by' => $userId]);
                    $term->delete();
                }
            }
        }


        return response()->json(['message' => 'Order updated successfully']);
    }

    /**
     * Catat pembayaran untuk order tertentu.
     * Status otomatis: lunas bila total bayar >= grand total,
     * down payment bila sebagian, belum lunas bila 0.
     */
    public function storePayment(Request $request, $invoice_no)
    {
        $userId = $request->user()->id;
        $order = Order::with(['items', 'payments'])->where('invoice_no', $invoice_no)->firstOrFail();

        $data = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'comment' => 'nullable|string',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('payments', 'public');
        }

        $order->payments()->create([
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'proof_file' => $proofPath,
            'comment' => $data['comment'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $grandTotal = $this->computeGrandTotal($order);
        $totalPaid = $order->payments()->sum('amount');
        $status = $totalPaid >= $grandTotal
            ? 'Lunas'
            : ($totalPaid > 0 ? 'Down Payment' : 'Belum Lunas');

        $order->update(['status' => $status, 'updated_by' => $userId]);

        return response()->json([
            'message' => 'Payment recorded successfully',
            'status' => $status,
            'totalPaid' => $totalPaid,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function storeTermPayment(Request $request, $invoice_no, $term_id)
    {
        $userId = $request->user()->id;
        $order = Order::with(['terms'])->where('invoice_no', $invoice_no)->firstOrFail();
        $term = $order->terms()->findOrFail($term_id);

        $data = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'comment' => 'nullable|string',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $proofPath = $request->file('proof_file')->store('payments', 'public');
        }

        $term->payments()->create([
            'order_id' => $order->id,
            'payment_date' => $data['payment_date'],
            'amount' => $data['amount'],
            'proof_file' => $proofPath,
            'comment' => $data['comment'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $termTotalPaid = $term->payments()->sum('amount');
        $grandTotal = $this->computeGrandTotal($order);
        $termExpected = $grandTotal * ($term->percent / 100);

        $term->update([
            'paid_amount' => $termTotalPaid,
            'is_paid' => $termTotalPaid >= $termExpected,
            'paid_at' => $termTotalPaid >= $termExpected ? now()->toDateString() : null,
            'updated_by' => $userId,
        ]);

        // Auto update order status based on ALL terms
        $allTerms = $order->terms()->get();
        if ($allTerms->count() > 0) {
            $allPaid = $allTerms->every(fn($t) => $t->is_paid);
            $anyPaid = $allTerms->contains(fn($t) => $t->paid_amount > 0);
            
            $status = 'Belum Lunas';
            if ($allPaid) {
                $status = 'Lunas';
            } elseif ($anyPaid) {
                $status = 'Down Payment';
            }
            $order->update(['status' => $status, 'updated_by' => $userId]);
        }

        return response()->json([
            'message' => 'Term payment recorded successfully',
        ]);
    }

    /**
     * Hitung grand total order dari item (mirip frontend calc).
     */
    private function computeGrandTotal($order)
    {
        $subtotal = 0;
        foreach ($order->items as $it) {
            $qty = (float) ($it->qty ?? 0);
            $markupPrice = (float) ($it->markup_price ?? 0);
            $subtotal += $qty * $markupPrice;
        }
        $discount = (float) ($order->discount ?? 0);
        $discountType = $order->discount_type ?? 'Rp';
        $discountAmount = $discountType === '%' ? round($subtotal * $discount / 100) : $discount;
        $afterDisc = max(0, $subtotal - $discountAmount);

        $serviceFee = (float) ($order->service_fee ?? 0);
        $serviceFeeType = $order->service_fee_type ?? 'Rp';
        $serviceFeeAmount = $serviceFeeType === '%' ? round($afterDisc * $serviceFee / 100) : $serviceFee;

        $taxPercent = (float) ($order->tax_percent ?? 0);
        $tax = round($afterDisc * $taxPercent / 100);

        return $afterDisc + $serviceFeeAmount + $tax;
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
            'bankAccounts' => 'nullable|array',
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

    /**
     * Buat customer/grup baru.
     */
    public function storeCustomer(Request $request)
    {
        $userId = $request->user()->id;

        $data = $request->validate([
            'name' => 'required|string',
            'pic_name' => 'nullable|string',
            'contact_info' => 'nullable|string',
        ]);

        $customer = \App\Models\Customer::create([
            'name' => $data['name'],
            'pic_name' => $data['pic_name'] ?? null,
            'contact_info' => $data['contact_info'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return response()->json([
            'message' => 'Customer created successfully',
            'customer' => $customer
        ], 201);
    }
}
