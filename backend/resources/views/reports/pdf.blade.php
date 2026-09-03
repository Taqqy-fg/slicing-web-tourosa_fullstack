@php
function fmtDatePdf($d) {
    if (!$d) return '-';
    $p = explode('-', $d);
    if (count($p) < 3) return $d;
    $m = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return intval($p[2]).' '.$m[intval($p[1])].' '.$p[0];
}
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan Tourosa</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #13233f; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #13233f; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .summary-box td { padding: 15px; border: 1px solid #ddd; text-align: center; width: 25%; }
        .summary-box .title { font-size: 11px; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .summary-box .value { font-size: 18px; font-weight: bold; color: #13233f; }
        .summary-box .profit { color: #1f7a5c; }
        table.data-table { width: 100%; border-collapse: collapse; }
        table.data-table th, table.data-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        table.data-table th { background-color: #f4f5f7; color: #333; font-weight: bold; }
        table.data-table .right { text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Laporan Keuangan & Penjualan Tourosa</h1>
        <p>Tanggal Cetak: {{ $date }}</p>
    </div>

    <table class="summary-box">
        <tr>
            <td>
                <div class="title">Total Pendapatan</div>
                <div class="value">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="title">Total Modal (HPP)</div>
                <div class="value">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="title">Estimasi Profit</div>
                <div class="value profit">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
            </td>
            <td>
                <div class="title">Margin Rata-rata</div>
                <div class="value">{{ $marginAvg }}%</div>
            </td>
        </tr>
    </table>

    <h3 style="color:#13233f;">Rincian Pesanan</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>No Invoice</th>
                <th>Grup</th>
                <th>Tanggal</th>
                <th class="right">Omset (Rp)</th>
                <th class="right">HPP (Rp)</th>
                <th class="right">Profit (Rp)</th>
                <th class="right">Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $o)
            <tr>
                <td>{{ $o['no'] }}</td>
                <td>{{ $o['group'] }}</td>
                <td>{{ fmtDatePdf($o['date']) }}</td>
                <td class="right">Rp {{ number_format($o['revenue'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($o['cost'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($o['profit'], 0, ',', '.') }}</td>
                <td class="right">{{ $o['margin'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
