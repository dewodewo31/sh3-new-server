<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ledger Pembukuan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { border-bottom: 2px solid #1f2937; padding-bottom: 6px; margin-bottom: 10px; }
        .brand { font-size: 16px; font-weight: bold; }
        .meta { font-size: 9px; color: #6b7280; margin-top: 2px; }
        h2 { font-size: 13px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 3px 5px; text-align: left; }
        th { background: #f3f4f6; font-weight: bold; }
        td.num { text-align: right; }
        .income { color: #047857; }
        .expense { color: #b91c1c; }
        tfoot td { font-weight: bold; background: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">SH3 &mdash; Samarinda Hash House Harriers</div>
        <div class="meta">Laporan Ledger Pembukuan</div>
        @if($event)
            <div class="meta">Event: {{ $event->title }}</div>
        @endif
        @if(! empty($filters['date_from']) || ! empty($filters['date_to']))
            <div class="meta">
                Periode:
                {{ $filters['date_from'] ?? '-' }}
                s/d
                {{ $filters['date_to'] ?? '-' }}
            </div>
        @endif
        <div class="meta">Dicetak: {{ $generatedAt->format('d/m/Y H:i') }}</div>
    </div>

    <h2>Detail Transaksi</h2>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Referensi</th>
                <th>Event</th>
                <th>Pos Kegiatan</th>
                <th>Rekening</th>
                <th>Tipe</th>
                <th>Status</th>
                <th>Sponsor</th>
                <th>Payee</th>
                <th>Keterangan</th>
                <th class="num">Pemasukan</th>
                <th class="num">Pengeluaran</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalIncome = 0;
                $totalExpense = 0;
            @endphp
            @forelse($rows as $row)
                @php
                    $income = $row->type === 'income' ? (float) $row->amount : 0;
                    $expense = $row->type === 'expense' ? (float) $row->amount : 0;
                    $totalIncome += $income;
                    $totalExpense += $expense;
                    $reference = $row->reference_type ? $row->reference_type.'#'.($row->reference_id ?? '') : '';
                @endphp
                <tr>
                    <td>{{ $row->transaction_date?->format('d/m/Y') ?? '' }}</td>
                    <td>{{ $reference }}</td>
                    <td>{{ $row->event?->title ?? '' }}</td>
                    <td>{{ $row->activity?->name ?? '' }}</td>
                    <td>{{ $row->financialAccount?->name ?? '' }}</td>
                    <td>{{ $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                    <td>{{ $row->status ?? '' }}</td>
                    <td>{{ $row->sponsor?->name ?? '' }}</td>
                    <td>{{ $row->payee ?? '' }}</td>
                    <td>{{ $row->description ?? '' }}</td>
                    <td class="num income">{{ $income ? number_format($income, 0, ',', '.') : '' }}</td>
                    <td class="num expense">{{ $expense ? number_format($expense, 0, ',', '.') : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center;">Belum ada transaksi.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="10">Total</td>
                <td class="num income">{{ number_format($totalIncome, 0, ',', '.') }}</td>
                <td class="num expense">{{ number_format($totalExpense, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
