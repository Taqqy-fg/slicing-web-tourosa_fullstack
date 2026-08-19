<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Order::with(['items', 'expenses'])->orderBy('id', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'Tanggal Berangkat',
            'Nama Grup',
            'Destinasi',
            'Jumlah Pax',
            'Total Harga Jual (Omset)',
            'Total Modal (HPP)',
            'Diskon',
            'Pajak',
            'Pengeluaran Tambahan (Expenses)',
            'Profit Bersih',
            'Margin (%)',
            'Status Pembayaran'
        ];
    }

    public function map($order): array
    {
        $subtotal = $order->items->sum(fn($i) => $i->qty * $i->price);
        $totalExpenses = $order->expenses->sum('amount');
        $totalCostOrder = $order->items->sum(fn($i) => $i->qty * $i->cost) + $totalExpenses;
        
        $discount = (float)$order->discount;
        $taxPercent = (float)$order->tax_percent;
        
        $afterDisc = $subtotal - $discount;
        $tax = $afterDisc * ($taxPercent / 100);
        $grandTotal = $afterDisc + $tax;
        
        $profit = $grandTotal - $totalCostOrder;
        $margin = $afterDisc > 0 ? round(($profit / $afterDisc) * 100) : 0;

        return [
            $order->invoice_no,
            $order->depart_date ?? '-',
            $order->group_name,
            $order->destination,
            $order->pax,
            $grandTotal,
            $totalCostOrder,
            $discount,
            $tax,
            $totalExpenses,
            $profit,
            $margin . '%',
            $order->status
        ];
    }
}
