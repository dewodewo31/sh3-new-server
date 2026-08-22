<?php

namespace App\Services;

use App\Models\Bookkeeping;
use App\Repositories\BookkeepingRepository;

class BookkeepingReportService
{
    public function __construct(
        private BookkeepingRepository $bookkeepingRepository,
    ) {}

    /**
     * Dashboard aggregates: period totals, per-account totals, bounded recent
     * transactions, and a per-month breakdown. All sums come from the repository
     * (SQL SUM/GROUP BY) — never a full-ledger PHP loop.
     */
    public function dashboard(array $filters): array
    {
        $filters = $this->activeFilters($filters);

        $recent = $this->bookkeepingRepository->filtered($filters, 10)->items();

        return [
            'periodTotals' => $this->bookkeepingRepository->periodTotals($filters),
            'perAccountTotals' => $this->bookkeepingRepository->perAccountTotals($filters),
            'perMonthTotals' => $this->monthlyTotals((int) ($filters['year'] ?? now()->year), $filters),
            'recent' => $recent,
        ];
    }

    /**
     * Cash-flow for a calendar year: opening (prior-period balance), monthly
     * income/expense, and closing balance. Cancelled records are excluded; paid
     * records are preserved in the totals.
     */
    public function cashFlow(array $filters, int $year): array
    {
        $filters = $this->activeFilters($filters);

        $opening = $this->bookkeepingRepository->periodTotals(
            array_merge($filters, ['to' => ($year - 1).'-12-31'])
        )['balance'];

        $months = $this->monthlyTotals($year, $filters);

        $income = 0.0;
        $expense = 0.0;
        foreach ($months as $month) {
            $income += $month['income'];
            $expense += $month['expense'];
        }

        return [
            'year' => $year,
            'opening' => $opening,
            'income' => $income,
            'expense' => $expense,
            'closing' => $opening + ($income - $expense),
            'months' => $months,
        ];
    }

    public function budgetVsActual(int $eventId): array
    {
        return $this->bookkeepingRepository->budgetVsActual($eventId);
    }

    public function receivable(array $filters): array
    {
        return $this->bookkeepingRepository->receivableSummary($filters);
    }

    public function payable(array $filters): array
    {
        return $this->bookkeepingRepository->payableSummary($filters);
    }

    /**
     * Per-month breakdown via the repository's periodTotals (SQL), excluding
     * cancelled records so the history stays clean.
     */
    private function monthlyTotals(int $year, array $filters): array
    {
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $from = sprintf('%04d-%02d-01', $year, $month);
            $to = date('Y-m-t', strtotime($from));

            $totals = $this->bookkeepingRepository->periodTotals(
                array_merge($filters, ['from' => $from, 'to' => $to])
            );

            $months[] = [
                'month' => $month,
                'income' => $totals['income'],
                'expense' => $totals['expense'],
                'balance' => $totals['balance'],
            ];
        }

        return $months;
    }

    /**
     * Exclude cancelled from income/expense totals unless the caller explicitly
     * scopes status. Paid records are always preserved.
     */
    private function activeFilters(array $filters): array
    {
        if (empty($filters['status'])) {
            $filters['status'] = array_values(array_diff(
                Bookkeeping::STATUSES,
                [Bookkeeping::STATUS_CANCELLED]
            ));
        }

        return $filters;
    }
}
