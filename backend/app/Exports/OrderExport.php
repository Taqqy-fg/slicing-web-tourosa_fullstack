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
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $query = Order::with(['orderInfo', 'items', 'expenses'])->orderBy('id', 'desc');
        
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('invoice_date', [$this->startDate, $this->endDate]);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'Tanggal Invoice',
            'Nama Grup',
            'Total Harga Jual (Omset)',
            'Total Modal (HPP)',
            'Diskon',
            'Service Fee',
            'Pajak',
            'Grand Total',
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
            'D' => 24,
            'E' => 20,
            'F' => 16,
            'G' => 16,
            'H' => 16,
            'I' => 20,
            'J' => 38,
            'K' => 20,
            'L' => 12,
            'M' => 18,
        ];
    }

    public function map($order): array
    {
        $subtotal = $order->items->sum(fn($i) => $i->qty * ($i->markup_price ?? 0));
        $totalCost = $order->items->sum(fn($i) => $i->qty * ($i->cost + ($i->markup_cost ?? 0)));
        $totalExpenses = $order->expenses->sum('amount');

        $discountType = $order->discount_type ?? 'Rp';
        $discount = (float) $order->discount;
        $discountAmount = $discountType === '%' ? $subtotal * $discount / 100 : $discount;

        $afterDisc = max(0, $subtotal - $discountAmount);
        $serviceFee = (float) ($order->service_fee ?? 0);
        $taxPercent = (float) $order->tax_percent;
        $tax = $afterDisc * ($taxPercent / 100);
        $grandTotal = $afterDisc + $serviceFee + $tax;

        $profit = $afterDisc - $totalCost - $totalExpenses;
        $margin = $afterDisc > 0 ? round(($profit / $afterDisc) * 100) : 0;

        $dateStr = $order->invoice_date ?? null;
        $dateFormatted = '-';
        if ($dateStr) {
            $p = explode('-', $dateStr);
            if (count($p) === 3) {
                $dateFormatted = Carbon::create(intval($p[0]), intval($p[1]), intval($p[2]))->format('d/m/Y');
            }
        }

        return [
            $order->invoice_no,
            $dateFormatted,
            $order->orderInfo?->group_name,
            $subtotal,
            $totalCost + $totalExpenses,
            $discountAmount,
            $serviceFee,
            $tax,
            $grandTotal,
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
