<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hutang</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 6px; margin-bottom: 10px; }
        .brand { font-size: 16px; font-weight: bold; }
        .meta { font-size: 9px; color: #6b7280; margin-top: 2px; }
        h2 { font-size: 13px; margin: 0 0 8px; }
        .summary { margin-bottom: 12px; }
        .summary div { margin-bottom: 6px; }
        .summary b { font-size: 15px; }
        .pos { color: #047857; }
        .neg { color: #b91c1c; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">SH3 &mdash; Samarinda Hash House Harriers</div>
        <div class="meta">Laporan Hutang</div>
        @if($event)
            <div class="meta">Event: {{ $event->title }}</div>
        @endif
        <div class="meta">Dicetak: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    <h2>Ringkasan Hutang</h2>

    <div class="summary">
        <div>Komitmen: <b>{{ number_format($summary['committed'], 0, ',', '.') }}</b></div>
        <div class="pos">Dibayar: <b>{{ number_format($summary['paid'], 0, ',', '.') }}</b></div>
        <div class="{{ $summary['outstanding'] >= 0 ? 'pos' : 'neg' }}">Sisa Hutang: <b>{{ number_format($summary['outstanding'], 0, ',', '.') }}</b></div>
    </div>
</body>
</html>
