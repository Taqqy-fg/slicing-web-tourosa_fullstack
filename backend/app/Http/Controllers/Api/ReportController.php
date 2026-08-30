<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrderExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Download laporan dalam format Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        $timestamp = now()->format('d-m-Y');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        return Excel::download(new OrderExport($startDate, $endDate), "Laporan_Tourosa_{$timestamp}.xlsx");
    }

    /**
     * Download laporan dalam format PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = Order::with(['orderInfo', 'items', 'expenses', 'terms'])->orderBy('id', 'desc');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        $orders = $query->get();

        // Calculate totals for PDF summary
        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;

        $processedOrders = $orders->map(function ($o) use (&$totalRevenue, &$totalCost, &$totalProfit) {
            $subtotal = $o->items->sum(fn($i) => $i->qty * ($i->markup_price ?? 0));
            $totalCostOrder = $o->items->sum(fn($i) => $i->qty * ($i->cost + ($i->markup_cost ?? 0))) + $o->expenses->sum('amount');
            
            $discountType = $o->discount_type ?? 'Rp';
            $discount = (float)$o->discount;
            $discountAmount = $discountType === '%' ? $subtotal * $discount / 100 : $discount;
            
            $afterDisc = max(0, $subtotal - $discountAmount);
            $serviceFee = (float) ($o->service_fee ?? 0);
            $taxPercent = (float)$o->tax_percent;
            $tax = $afterDisc * ($taxPercent / 100);
            $grandTotal = $afterDisc + $serviceFee + $tax;
            
            $profit = $afterDisc - $totalCostOrder;
            
            $totalRevenue += $grandTotal;
            $totalCost += $totalCostOrder;
            $totalProfit += $profit;

            return [
                'no' => $o->invoice_no,
                'group' => $o->orderInfo?->group_name,
                'date' => $o->invoice_date,
                'revenue' => $grandTotal,
                'cost' => $totalCostOrder,
                'profit' => $profit,
                'margin' => $afterDisc > 0 ? round(($profit / $afterDisc) * 100) : 0,
            ];
        });

        $marginAvg = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100) : 0;

        $data = [
            'orders' => $processedOrders,
            'totalRevenue' => $totalRevenue,
            'totalCost' => $totalCost,
            'totalProfit' => $totalProfit,
            'marginAvg' => $marginAvg,
            'date' => now()->format('d M Y')
        ];

        $pdf = Pdf::loadView('reports.pdf', $data);
        $timestamp = now()->format('d-m-Y');
        return $pdf->download("Laporan_Tourosa_{$timestamp}.pdf");
    }
}
