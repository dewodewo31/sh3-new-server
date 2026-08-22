<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Bookkeeping;
use App\Models\Event;
use App\Repositories\BookkeepingRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;

class BookkeepingExportService
{
    public function __construct(
        private BookkeepingRepository $bookkeepingRepository,
        private BookkeepingReportService $bookkeepingReportService,
    ) {}

    // --- Transactions ledger (a) ---

    public function transactions(string $format, array $filters): Response
    {
        return $this->dispatch($format, 'ledger', function () use ($filters) {
            return $this->csvTransactions($filters);
        }, function () use ($filters) {
            return $this->xlsxTransactions($filters);
        }, function () use ($filters) {
            return $this->pdfTransactions($filters);
        }, $filters);
    }

    // --- Budget vs actual (b) ---

    public function budgetVsActual(string $format, int $eventId): Response
    {
        return $this->dispatch($format, 'budget-vs-actual', function () use ($eventId) {
            return $this->csvBudgetVsActual($eventId);
        }, function () use ($eventId) {
            return $this->xlsxBudgetVsActual($eventId);
        }, function () use ($eventId) {
            return $this->pdfBudgetVsActual($eventId);
        }, ['event_id' => $eventId]);
    }

    // --- Cash flow (c) ---

    public function cashFlow(string $format, array $filters, int $year): Response
    {
        return $this->dispatch($format, 'cash-flow', function () use ($filters, $year) {
            return $this->csvCashFlow($filters, $year);
        }, function () use ($filters, $year) {
            return $this->xlsxCashFlow($filters, $year);
        }, function () use ($filters, $year) {
            return $this->pdfCashFlow($filters, $year);
        }, $filters);
    }

    // --- Receivables (d) ---

    public function receivables(string $format, array $filters): Response
    {
        return $this->dispatch($format, 'receivables', function () use ($filters) {
            return $this->csvSummary('receivables', $this->bookkeepingReportService->receivable($filters));
        }, function () use ($filters) {
            return $this->xlsxSummary('receivables', $this->bookkeepingReportService->receivable($filters));
        }, function () use ($filters) {
            return $this->pdfSummary('receivables', $this->bookkeepingReportService->receivable($filters), $filters);
        }, $filters);
    }

    // --- Payables (e) ---

    public function payable(string $format, array $filters): Response
    {
        return $this->dispatch($format, 'payables', function () use ($filters) {
            return $this->csvSummary('payables', $this->bookkeepingReportService->payable($filters));
        }, function () use ($filters) {
            return $this->xlsxSummary('payables', $this->bookkeepingReportService->payable($filters));
        }, function () use ($filters) {
            return $this->pdfSummary('payables', $this->bookkeepingReportService->payable($filters), $filters);
        }, $filters);
    }

    // --- Format dispatch ---

    private function dispatch(string $format, string $label, callable $csv, callable $xlsx, callable $pdf, array $filters): Response
    {
        $format = strtolower($format);

        return match ($format) {
            'xlsx' => $xlsx(),
            'pdf' => $pdf(),
            default => $csv(),
        };
    }

    // --- Filename ---

    private function filename(string $label, string $ext, array $filters): string
    {
        $parts = ['sh3-bookkeepings', $label];

        if (! empty($filters['event_id'])) {
            $event = Event::find($filters['event_id']);
            $slug = $event?->slug ?: $filters['event_id'];
            $parts[] = $slug;
        }

        $parts[] = now()->format('Y-m');

        return implode('-', $parts).'.'.$ext;
    }

    // --- Transactions: row mapping ---

    private function transactionRow(Bookkeeping $row): array
    {
        $income = $row->type === 'income' ? (float) $row->amount : 0.0;
        $expense = $row->type === 'expense' ? (float) $row->amount : 0.0;

        $reference = '';
        if (! empty($row->reference_type)) {
            $reference = $row->reference_type.'#'.($row->reference_id ?? '');
        }

        return [
            $row->transaction_date?->format('Y-m-d') ?? '',
            $reference,
            $row->event?->title ?? '',
            $row->activity?->name ?? '',
            $row->financialAccount?->name ?? '',
            $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            $row->status ?? '',
            $row->sponsor?->name ?? '',
            $row->payee ?? '',
            $row->description ?? '',
            $income,
            $expense,
        ];
    }

    private function transactionHeaders(): array
    {
        return [
            'Tanggal', 'Referensi', 'Event', 'Pos Kegiatan', 'Rekening',
            'Tipe', 'Status', 'Sponsor', 'Payee', 'Keterangan',
            'Pemasukan', 'Pengeluaran',
        ];
    }

    // --- Transactions: CSV ---

    private function csvTransactions(array $filters): Response
    {
        $headers = $this->transactionHeaders();
        $filename = $this->filename('ledger', 'csv', $filters);

        return response()->stream(function () use ($filters, $headers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, $headers);

            $this->bookkeepingRepository->exportQuery($filters)->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, $this->transactionRow($row));
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // --- Transactions: XLSX ---

    private function xlsxTransactions(array $filters): Response
    {
        $headers = $this->transactionHeaders();
        $filename = $this->filename('ledger', 'xlsx', $filters);

        return response()->stream(function () use ($filters, $headers) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName('Transaksi');
            $writer->addRow(Row::fromValues($headers));

            $this->bookkeepingRepository->exportQuery($filters)->chunk(500, function ($rows) use ($writer) {
                foreach ($rows as $row) {
                    $writer->addRow(Row::fromValues($this->transactionRow($row)));
                }
            });

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // --- Transactions: PDF ---

    private function pdfTransactions(array $filters): Response
    {
        $rows = $this->bookkeepingRepository->exportQuery($filters)->get();
        $event = ! empty($filters['event_id']) ? Event::find($filters['event_id']) : null;

        return Pdf::loadView('bookkeepings.exports.ledger', [
            'rows' => $rows,
            'filters' => $filters,
            'event' => $event,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($this->filename('ledger', 'pdf', $filters));
    }

    // --- Budget vs actual ---

    private function budgetRows(int $eventId): array
    {
        $rows = $this->bookkeepingRepository->budgetVsActual($eventId);
        $activityIds = array_column($rows, 'activity_id');
        $names = Activity::whereIn('id', $activityIds)->pluck('name', 'id');

        return array_map(function ($row) use ($names) {
            return [
                'activity' => $names[$row['activity_id']] ?? ('#'.$row['activity_id']),
                'budget' => (float) $row['budget'],
                'actual_income' => (float) $row['actual_income'],
                'actual_expense' => (float) $row['actual_expense'],
                'variance' => (float) $row['variance'],
            ];
        }, $rows);
    }

    private function budgetHeaders(): array
    {
        return ['Pos Kegiatan', 'Anggaran', 'Realisasi Pemasukan', 'Realisasi Pengeluaran', 'Selisih'];
    }

    private function csvBudgetVsActual(int $eventId): Response
    {
        $headers = $this->budgetHeaders();
        $filename = $this->filename('budget-vs-actual', 'csv', ['event_id' => $eventId]);

        return response()->stream(function () use ($eventId, $headers) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($this->budgetRows($eventId) as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function xlsxBudgetVsActual(int $eventId): Response
    {
        $headers = $this->budgetHeaders();
        $filename = $this->filename('budget-vs-actual', 'xlsx', ['event_id' => $eventId]);

        return response()->stream(function () use ($eventId, $headers) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName('Budget vs Actual');
            $writer->addRow(Row::fromValues($headers));

            foreach ($this->budgetRows($eventId) as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function pdfBudgetVsActual(int $eventId): Response
    {
        $event = Event::find($eventId);

        return Pdf::loadView('bookkeepings.exports.budget-vs-actual', [
            'rows' => $this->budgetRows($eventId),
            'event' => $event,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->download($this->filename('budget-vs-actual', 'pdf', ['event_id' => $eventId]));
    }

    // --- Cash flow ---

    private function cashFlowHeaders(): array
    {
        return ['Bulan', 'Pemasukan', 'Pengeluaran', 'Saldo'];
    }

    private function csvCashFlow(array $filters, int $year): Response
    {
        $report = $this->bookkeepingReportService->cashFlow($filters, $year);
        $filename = $this->filename('cash-flow', 'csv', $filters);

        return response()->stream(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Arus Kas '.$report['year']]);
            fputcsv($handle, ['Saldo Awal', $report['opening']]);
            fputcsv($handle, ['Pemasukan', $report['income']]);
            fputcsv($handle, ['Pengeluaran', $report['expense']]);
            fputcsv($handle, ['Saldo Akhir', $report['closing']]);
            fputcsv($handle, []);
            fputcsv($handle, $this->cashFlowHeaders());

            foreach ($report['months'] as $month) {
                fputcsv($handle, [$month['month'], $month['income'], $month['expense'], $month['balance']]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function xlsxCashFlow(array $filters, int $year): Response
    {
        $report = $this->bookkeepingReportService->cashFlow($filters, $year);
        $filename = $this->filename('cash-flow', 'xlsx', $filters);

        return response()->stream(function () use ($report) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName('Arus Kas');
            $writer->addRow(Row::fromValues(['Arus Kas '.$report['year']]));
            $writer->addRow(Row::fromValues(['Saldo Awal', $report['opening']]));
            $writer->addRow(Row::fromValues(['Pemasukan', $report['income']]));
            $writer->addRow(Row::fromValues(['Pengeluaran', $report['expense']]));
            $writer->addRow(Row::fromValues(['Saldo Akhir', $report['closing']]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues($this->cashFlowHeaders()));

            foreach ($report['months'] as $month) {
                $writer->addRow(Row::fromValues([$month['month'], $month['income'], $month['expense'], $month['balance']]));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function pdfCashFlow(array $filters, int $year): Response
    {
        $report = $this->bookkeepingReportService->cashFlow($filters, $year);
        $event = ! empty($filters['event_id']) ? Event::find($filters['event_id']) : null;

        return Pdf::loadView('bookkeepings.exports.cash-flow', [
            'report' => $report,
            'filters' => $filters,
            'event' => $event,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->download($this->filename('cash-flow', 'pdf', $filters));
    }

    // --- Receivables / Payables summary ---

    private function summaryRows(string $type, array $summary): array
    {
        if ($type === 'receivables') {
            return [
                ['Komitmen', $summary['committed']],
                ['Diterima', $summary['received']],
                ['Sisa Piutang', $summary['outstanding']],
            ];
        }

        return [
            ['Komitmen', $summary['committed']],
            ['Dibayar', $summary['paid']],
            ['Sisa Hutang', $summary['outstanding']],
        ];
    }

    private function csvSummary(string $type, array $summary): Response
    {
        $filename = $this->filename($type, 'csv', []);

        return response()->stream(function () use ($type, $summary) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Keterangan', 'Nilai']);

            foreach ($this->summaryRows($type, $summary) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function xlsxSummary(string $type, array $summary): Response
    {
        $filename = $this->filename($type, 'xlsx', []);

        return response()->stream(function () use ($type, $summary) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->getCurrentSheet()->setName(ucfirst($type));
            $writer->addRow(Row::fromValues(['Keterangan', 'Nilai']));

            foreach ($this->summaryRows($type, $summary) as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function pdfSummary(string $type, array $summary, array $filters): Response
    {
        $event = ! empty($filters['event_id']) ? Event::find($filters['event_id']) : null;

        return Pdf::loadView('bookkeepings.exports.'.$type, [
            'summary' => $summary,
            'filters' => $filters,
            'event' => $event,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait')->download($this->filename($type, 'pdf', $filters));
    }
}
