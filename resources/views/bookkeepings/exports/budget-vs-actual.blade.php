<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Budget vs Actual</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 6px; margin-bottom: 10px; }
        .brand { font-size: 16px; font-weight: bold; }
        .meta { font-size: 9px; color: #6b7280; margin-top: 2px; }
        h2 { font-size: 13px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; }
        td.num { text-align: right; }
        .pos { color: #047857; }
        .neg { color: #b91c1c; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">SH3 &mdash; Samarinda Hash House Harriers</div>
        <div class="meta">Laporan Budget vs Actual</div>
        @if($event)
            <div class="meta">Event: {{ $event->title }}</div>
        @endif
        <div class="meta">Dicetak: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    <h2>Anggaran vs Realisasi per Pos Kegiatan</h2>

    <table>
        <thead>
            <tr>
                <th>Pos Kegiatan</th>
                <th class="num">Anggaran</th>
                <th class="num">Realisasi Pemasukan</th>
                <th class="num">Realisasi Pengeluaran</th>
                <th class="num">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totBudget = 0; $totIncome = 0; $totExpense = 0; $totVariance = 0;
            @endphp
            @forelse($rows as $row)
                @php
                    $totBudget += $row['budget'];
                    $totIncome += $row['actual_income'];
                    $totExpense += $row['actual_expense'];
                    $totVariance += $row['variance'];
                @endphp
                <tr>
                    <td>{{ $row['activity'] }}</td>
                    <td class="num">{{ number_format($row['budget'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['actual_income'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['actual_expense'], 0, ',', '.') }}</td>
                    <td class="num {{ $row['variance'] >= 0 ? 'pos' : 'neg' }}">{{ number_format($row['variance'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;">Belum ada data budget.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="num">{{ number_format($totBudget, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($totIncome, 0, ',', '.') }}</td>
                <td class="num">{{ number_format($totExpense, 0, ',', '.') }}</td>
                <td class="num {{ $totVariance >= 0 ? 'pos' : 'neg' }}">{{ number_format($totVariance, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
