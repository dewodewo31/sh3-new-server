<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookkeepingRequest;
use App\Repositories\BookkeepingRepository;
use App\Repositories\EventRepository;
use App\Repositories\SponsorRepository;
use App\Services\BookkeepingExportService;
use App\Services\BookkeepingReportService;
use App\Services\BookkeepingService;
use App\Services\FileService;
use Illuminate\Support\Facades\Auth;

class BookkeepingController extends Controller
{
    public function __construct(
        private BookkeepingRepository $bookkeepingRepository,
        private BookkeepingService $bookkeepingService,
        private BookkeepingReportService $bookkeepingReportService,
        private BookkeepingExportService $bookkeepingExportService,
        private SponsorRepository $sponsorRepository,
        private EventRepository $eventRepository,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $filters = request()->only([
            'type', 'category', 'sponsor_id', 'event_id',
            'status', 'financial_account_id', 'activity_id',
        ]);
        $entries = $this->bookkeepingRepository->filtered($filters);
        $totals = $this->bookkeepingRepository->totals($filters);
        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->all();

        return view('bookkeepings.index', compact('entries', 'totals', 'filters', 'sponsors', 'events'));
    }

    public function create()
    {
        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->all();

        return view('bookkeepings.create', compact('sponsors', 'events'));
    }

    public function store(BookkeepingRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            $data['receipt'] = $this->fileService->upload($request->file('receipt'), 'bookkeepings');
        }

        try {
            $this->bookkeepingService->create($data, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil ditambahkan');
    }

    public function show(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id, ['sponsor', 'event', 'createdBy']);

        return view('bookkeepings.show', compact('entry'));
    }

    public function edit(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);
        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->all();

        return view('bookkeepings.edit', compact('entry', 'sponsors', 'events'));
    }

    public function update(int $id, BookkeepingRequest $request)
    {
        $entry = $this->bookkeepingRepository->findById($id);
        $data = $request->validated();

        if ($data['category'] !== 'sponsor') {
            $data['sponsor_id'] = null;
        }

        if ($data['category'] !== 'event_income') {
            $data['event_id'] = null;
        }

        if ($request->hasFile('receipt')) {
            $this->fileService->delete($entry->receipt);
            $data['receipt'] = $this->fileService->upload($request->file('receipt'), 'bookkeepings');
        }

        try {
            $this->bookkeepingService->update($entry, $data, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);

        if ($entry->isPaid()) {
            abort(403, 'Paid bookkeeping entries cannot be deleted.');
        }

        try {
            if ($entry->receipt) {
                $this->fileService->delete($entry->receipt);
            }
            $this->bookkeepingService->delete($entry, Auth::user());
        } catch (\InvalidArgumentException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil dihapus');
    }

    public function submit(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);

        try {
            $this->bookkeepingService->submit($entry, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil disubmit');
    }

    public function approve(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);

        try {
            $this->bookkeepingService->approve($entry, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil disetujui');
    }

    public function markPaid(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);

        try {
            $this->bookkeepingService->markPaid($entry, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil ditandai lunas');
    }

    public function cancel(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);

        try {
            $this->bookkeepingService->cancel($entry, Auth::user());
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil dibatalkan');
    }

    public function reports()
    {
        $filters = request()->only([
            'type', 'category', 'sponsor_id', 'event_id',
            'status', 'financial_account_id', 'activity_id',
            'payee', 'due_date', 'reference_type', 'reference_id', 'approved_by', 'year',
        ]);
        $data = $this->bookkeepingReportService->dashboard($filters);

        return view('bookkeepings.reports', array_merge($data, [
            'events' => $this->eventRepository->all(),
        ]));
    }

    public function receivables()
    {
        $filters = request()->only([
            'type', 'category', 'sponsor_id', 'event_id',
            'status', 'financial_account_id', 'activity_id',
            'payee', 'due_date', 'reference_type', 'reference_id', 'approved_by',
        ]);
        $data = $this->bookkeepingReportService->receivable($filters);

        return view('bookkeepings.receivables', $data);
    }

    public function payables()
    {
        $filters = request()->only([
            'type', 'category', 'sponsor_id', 'event_id',
            'status', 'financial_account_id', 'activity_id',
            'payee', 'due_date', 'reference_type', 'reference_id', 'approved_by',
        ]);
        $data = $this->bookkeepingReportService->payable($filters);

        return view('bookkeepings.payables', $data);
    }

    public function cashFlow()
    {
        $filters = request()->only([
            'type', 'category', 'sponsor_id', 'event_id',
            'status', 'financial_account_id', 'activity_id',
            'payee', 'due_date', 'reference_type', 'reference_id', 'approved_by',
        ]);
        $year = (int) request('year', now()->year);
        $data = $this->bookkeepingReportService->cashFlow($filters, $year);

        return view('bookkeepings.cash-flow', $data);
    }

    // --- Exports (CSV / XLSX / PDF) ---

    private function exportFilters(): array
    {
        return request()->only([
            'type', 'category', 'sponsor_id', 'event_id',
            'status', 'financial_account_id', 'activity_id',
            'date_from', 'date_to',
        ]);
    }

    public function export()
    {
        return $this->bookkeepingExportService->transactions(
            request('format', 'csv'),
            $this->exportFilters()
        );
    }

    public function exportBudgetVsActual()
    {
        $eventId = (int) request('event_id', 0);

        if ($eventId <= 0) {
            abort(404, 'Event wajib dipilih untuk ekspor budget vs actual.');
        }

        return $this->bookkeepingExportService->budgetVsActual(
            request('format', 'csv'),
            $eventId
        );
    }

    public function exportCashFlow()
    {
        return $this->bookkeepingExportService->cashFlow(
            request('format', 'csv'),
            $this->exportFilters(),
            (int) request('year', now()->year)
        );
    }

    public function exportReceivables()
    {
        return $this->bookkeepingExportService->receivables(
            request('format', 'csv'),
            $this->exportFilters()
        );
    }

    public function exportPayable()
    {
        return $this->bookkeepingExportService->payable(
            request('format', 'csv'),
            $this->exportFilters()
        );
    }
}
