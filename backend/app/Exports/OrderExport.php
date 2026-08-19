<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class OrderExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
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

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 18,
            'C' => 36,
            'D' => 22,
            'E' => 12,
            'F' => 24,
            'G' => 20,
            'H' => 16,
            'I' => 16,
            'J' => 38,
            'K' => 20,
            'L' => 12,
            'M' => 18,
        ];
    }

    public function map($order): array
    {
        $subtotal = $order->items->sum(fn($i) => $i->qty * $i->price);
        $totalExpenses = $order->expenses->sum('amount');
        $totalCostOrder = $order->items->sum(fn($i) => $i->qty * $i->cost) + $totalExpenses;

        $discount = (float) $order->discount;
        $taxPercent = (float) $order->tax_percent;

        $afterDisc = $subtotal - $discount;
        $tax = $afterDisc * ($taxPercent / 100);
        $grandTotal = $afterDisc + $tax;

        $profit = $grandTotal - $totalCostOrder;
        $margin = $afterDisc > 0 ? round(($profit / $afterDisc) * 100) : 0;

        $dateStr = $order->depart_date ?? null;
        $dateFormatted = '-';

        if ($dateStr && $dateStr !== '-') {
            $p = explode('-', $dateStr);
            if (count($p) === 3) {
                $dateFormatted = Carbon::create(intval($p[0]), intval($p[1]), intval($p[2]))->format('d/m/Y');
            }
        }

        return [
            $order->invoice_no,
            $dateFormatted,
            $order->group_name,
            $order->destination,
            $order->pax,
            $grandTotal,
            $totalCostOrder,
            $discount,
            $tax,
            $totalExpenses,
            $profit,
            $margin,
            $order->status
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}