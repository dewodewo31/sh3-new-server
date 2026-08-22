<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Arus Kas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 6px; margin-bottom: 10px; }
        .brand { font-size: 16px; font-weight: bold; }
        .meta { font-size: 9px; color: #6b7280; margin-top: 2px; }
        h2 { font-size: 13px; margin: 0 0 8px; }
        .summary { margin-bottom: 12px; }
        .summary span { display: inline-block; margin-right: 18px; }
        .summary b { font-size: 13px; }
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
        <div class="meta">Laporan Arus Kas &mdash; {{ $report['year'] }}</div>
        @if($event)
            <div class="meta">Event: {{ $event->title }}</div>
        @endif
        <div class="meta">Dicetak: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    <div class="summary">
        <span>Saldo Awal: <b>{{ number_format($report['opening'], 0, ',', '.') }}</b></span>
        <span class="pos">Pemasukan: <b>{{ number_format($report['income'], 0, ',', '.') }}</b></span>
        <span class="neg">Pengeluaran: <b>{{ number_format($report['expense'], 0, ',', '.') }}</b></span>
        <span>Saldo Akhir: <b>{{ number_format($report['closing'], 0, ',', '.') }}</b></span>
    </div>

    <h2>Arus Kas Bulanan</h2>

    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th class="num">Pemasukan</th>
                <th class="num">Pengeluaran</th>
                <th class="num">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report['months'] as $month)
                <tr>
                    <td>{{ $month['month'] }}</td>
                    <td class="num">{{ number_format($month['income'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($month['expense'], 0, ',', '.') }}</td>
                    <td class="num {{ $month['balance'] >= 0 ? 'pos' : 'neg' }}">{{ number_format($month['balance'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
