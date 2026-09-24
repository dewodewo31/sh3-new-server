<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryExternalLoanRequest;
use App\Http\Requests\InventoryHandoverRequest;
use App\Http\Requests\InventoryReturnRequest;
use App\Repositories\InventoryExternalLoanRepository;
use App\Services\InventoryExternalLoanService;

class InventoryExternalLoanController extends Controller
{
    public function __construct(
        private InventoryExternalLoanRepository $inventoryExternalLoanRepository,
        private InventoryExternalLoanService $inventoryExternalLoanService,
    ) {}

    public function index()
    {
        $filters = request()->only(['search', 'status', 'overdue']);
        $loans = $this->inventoryExternalLoanRepository->search($filters);

        return view('inventory.external-loans.index', compact('loans', 'filters'));
    }

    public function create()
    {
        return view('inventory.external-loans.create');
    }

    public function store(InventoryExternalLoanRequest $request)
    {
        $loan = $this->inventoryExternalLoanService->createLoan($request->validated());

        return redirect()->route('admin.inventory.external-loans.show', $loan->id)->with('success', 'Pinjaman barang berhasil dibuat');
    }

    public function show(int $id)
    {
        $loan = $this->inventoryExternalLoanRepository->findById($id, ['handovers.receiver', 'handovers.sender', 'createdBy', 'approvedBy', 'returnedReceivedBy']);

        return view('inventory.external-loans.show', compact('loan'));
    }

    public function handover(int $id, InventoryHandoverRequest $request)
    {
        $loan = $this->inventoryExternalLoanRepository->findById($id);

        $this->inventoryExternalLoanService->handover($loan, $request->validated());

        return redirect()->route('admin.inventory.external-loans.show', $id)->with('success', 'Handover berhasil dicatat');
    }

    public function returnItem(int $id, InventoryReturnRequest $request)
    {
        $loan = $this->inventoryExternalLoanRepository->findById($id);

        $this->inventoryExternalLoanService->returnItem($loan, $request->validated());

        return redirect()->route('admin.inventory.external-loans.show', $id)->with('success', 'Pengembalian berhasil dicatat');
    }

    public function cancel(int $id)
    {
        $loan = $this->inventoryExternalLoanRepository->findById($id);

        $this->inventoryExternalLoanService->cancelLoan($loan);

        return redirect()->route('admin.inventory.external-loans.show', $id)->with('success', 'Pinjaman berhasil dibatalkan');
    }
}
