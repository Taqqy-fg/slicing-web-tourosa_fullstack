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
    public function exportExcel(Request $request)
    {
        $timestamp = now()->format('d-m-Y');
        return Excel::download(new OrderExport, "Laporan_Tourosa_{$timestamp}.xlsx");
    }

    public function exportPdf(Request $request)
    {
        $orders = Order::with(['items', 'expenses', 'terms'])->orderBy('id', 'desc')->get();

        // Calculate totals for PDF summary
        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;

        $processedOrders = $orders->map(function ($o) use (&$totalRevenue, &$totalCost, &$totalProfit) {
            $subtotal = $o->items->sum(fn($i) => $i->qty * $i->price);
            $totalCostOrder = $o->items->sum(fn($i) => $i->qty * $i->cost) + $o->expenses->sum('amount');
            
            $discount = (float)$o->discount;
            $taxPercent = (float)$o->tax_percent;
            
            $afterDisc = $subtotal - $discount;
            $tax = $afterDisc * ($taxPercent / 100);
            $grandTotal = $afterDisc + $tax;
            
            $profit = $grandTotal - $totalCostOrder;
            
            $totalRevenue += $grandTotal;
            $totalCost += $totalCostOrder;
            $totalProfit += $profit;

            return [
                'no' => $o->invoice_no,
                'group' => $o->group_name,
                'date' => $o->depart_date ?? $o->invoice_date,
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
