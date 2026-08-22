<?php

namespace App\Repositories;

use App\Models\Bookkeeping;
use App\Support\Sort;
use Illuminate\Support\Facades\DB;

class BookkeepingRepository extends BaseRepository
{
    public function __construct(Bookkeeping $model)
    {
        parent::__construct($model);
    }

    public function filtered(array $filters, int $perPage = 15)
    {
        return $this->exportQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Builder with every supported filter + sort applied (no pagination), so the
     * export service can stream/chunk the same dataset the index page shows.
     */
    public function exportQuery(array $filters)
    {
        $query = $this->model->with(['sponsor', 'event', 'financialAccount', 'activity']);
        $this->applyFilters($query, $filters);

        Sort::apply($query, ['transaction_date', 'amount', 'type', 'category'], 'transaction_date', 'desc');

        return $query;
    }

    public function totals(array $filters): array
    {
        $query = $this->model->query();
        $this->applyFilters($query, $filters);

        $income = (float) (clone $query)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['sponsor_id'])) {
            $query->where('sponsor_id', $filters['sponsor_id']);
        }

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['financial_account_id'])) {
            $query->where('financial_account_id', $filters['financial_account_id']);
        }

        if (! empty($filters['activity_id'])) {
            $query->where('activity_id', $filters['activity_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }
    }

    // --- Financial reporting filters & aggregations (SQL-only, never load full ledger) ---

    private function applyFinancialFilters($query, array $filters): void
    {
        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['financial_account_id'])) {
            $query->where('financial_account_id', $filters['financial_account_id']);
        }

        if (! empty($filters['activity_id'])) {
            $query->where('activity_id', $filters['activity_id']);
        }

        if (! empty($filters['payee'])) {
            $query->where('payee', $filters['payee']);
        }

        if (! empty($filters['due_date'])) {
            $query->whereDate('due_date', $filters['due_date']);
        }

        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (! empty($filters['reference_id'])) {
            $query->where('reference_id', $filters['reference_id']);
        }

        if (! empty($filters['approved_by'])) {
            $query->where('approved_by', $filters['approved_by']);
        }
    }

    public function periodTotals(array $filters): array
    {
        $query = $this->model->query();
        $this->applyFinancialFilters($query, $filters);

        if (! empty($filters['from'])) {
            $query->where('transaction_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('transaction_date', '<=', $filters['to']);
        }

        $row = (clone $query)->selectRaw(
            "SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,"
            ." SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense"
        )->first();

        $income = (float) ($row->income ?? 0);
        $expense = (float) ($row->expense ?? 0);

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function perAccountTotals(array $filters): array
    {
        $query = $this->model->query();
        $this->applyFinancialFilters($query, $filters);

        return $query->whereNotNull('financial_account_id')
            ->selectRaw('financial_account_id as account_id')
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance")
            ->groupBy('financial_account_id')
            ->orderBy('financial_account_id')
            ->get()
            ->map(fn ($row) => [
                'account_id' => $row->account_id,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
                'balance' => (float) $row->balance,
            ])
            ->all();
    }

    public function perMonthTotals(int $year): array
    {
        return $this->model->query()
            ->whereYear('transaction_date', $year)
            ->selectRaw('MONTH(transaction_date) as month')
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => (int) $row->month,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
                'balance' => (float) $row->balance,
            ])
            ->all();
    }

    public function budgetVsActual(int $eventId): array
    {
        $actuals = $this->model->query()
            ->where('event_id', $eventId)
            ->whereNotNull('activity_id')
            ->selectRaw('activity_id')
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            ->groupBy('activity_id');

        return DB::table('event_budgets')
            ->where('event_budgets.event_id', $eventId)
            ->leftJoinSub($actuals, 'actuals', function ($join) {
                $join->on('event_budgets.activity_id', '=', 'actuals.activity_id');
            })
            ->selectRaw('event_budgets.activity_id')
            ->selectRaw('event_budgets.amount as budget')
            ->selectRaw('COALESCE(actuals.income, 0) as actual_income')
            ->selectRaw('COALESCE(actuals.expense, 0) as actual_expense')
            ->selectRaw('event_budgets.amount - (COALESCE(actuals.income, 0) - COALESCE(actuals.expense, 0)) as variance')
            ->orderBy('event_budgets.activity_id')
            ->get()
            ->map(fn ($row) => [
                'activity_id' => $row->activity_id,
                'budget' => (float) $row->budget,
                'actual_income' => (float) $row->actual_income,
                'actual_expense' => (float) $row->actual_expense,
                'variance' => (float) $row->variance,
            ])
            ->all();
    }

    public function receivableSummary(array $filters): array
    {
        $eventId = $filters['event_id'] ?? null;

        // ponytail: event_sponsors has no 'booked' status; committed = not rejected.
        $committed = 0.0;
        if (! empty($eventId)) {
            $committed = (float) DB::table('event_sponsors')
                ->where('event_id', $eventId)
                ->where('status', '!=', 'rejected')
                ->sum('value');
        }

        $receivedQuery = $this->model->query();
        $this->applyFinancialFilters($receivedQuery, $filters);
        if (! empty($eventId)) {
            $receivedQuery->where('event_id', $eventId);
        }
        $received = (float) $receivedQuery
            ->where('type', 'income')
            ->whereIn('status', [Bookkeeping::STATUS_SUBMITTED, Bookkeeping::STATUS_APPROVED])
            ->sum('amount');

        return [
            'committed' => $committed,
            'received' => $received,
            'outstanding' => $committed - $received,
        ];
    }

    public function payableSummary(array $filters): array
    {
        $base = $this->model->query();
        $this->applyFinancialFilters($base, $filters);
        $base->where('type', 'expense')->whereNotNull('payee');

        $committed = (float) (clone $base)
            ->whereIn('status', [Bookkeeping::STATUS_SUBMITTED, Bookkeeping::STATUS_APPROVED])
            ->sum('amount');

        $paid = (float) (clone $base)
            ->where('status', Bookkeeping::STATUS_PAID)
            ->sum('amount');

        return [
            'committed' => $committed,
            'paid' => $paid,
            'outstanding' => $committed - $paid,
        ];
    }
}
