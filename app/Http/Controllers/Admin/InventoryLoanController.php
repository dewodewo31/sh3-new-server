<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryDocumentRequest;
use App\Http\Requests\InventoryHandoverRequest;
use App\Http\Requests\InventoryLoanRequest;
use App\Http\Requests\InventoryReturnRequest;
use App\Models\InventoryDocument;
use App\Models\InventoryItem;
use App\Repositories\InventoryLoanRepository;
use App\Services\FileService;
use App\Services\InventoryLoanService;
use Illuminate\Support\Facades\Storage;

class InventoryLoanController extends Controller
{
    public function __construct(
        private InventoryLoanRepository $inventoryLoanRepository,
        private InventoryLoanService $inventoryLoanService,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $filters = request()->only(['search', 'status', 'overdue']);
        $loans = $this->inventoryLoanRepository->search($filters);

        return view('inventory.loans.index', compact('loans', 'filters'));
    }

    public function create()
    {
        $items = InventoryItem::query()
            ->where('status', InventoryItem::STATUS_AVAILABLE)
            ->orderBy('name')
            ->get();

        return view('inventory.loans.create', compact('items'));
    }

    public function store(InventoryLoanRequest $request)
    {
        $loan = $this->inventoryLoanService->createLoan($request->validated());

        return redirect()->route('admin.inventory.loans.show', $loan->id)->with('success', 'Peminjaman inventaris berhasil dibuat');
    }

    public function show(int $id)
    {
        $loan = $this->inventoryLoanRepository->findById($id, ['item', 'handovers.receiver', 'handovers.sender', 'documents', 'createdBy', 'approvedBy', 'returnedReceivedBy']);

        return view('inventory.loans.show', compact('loan'));
    }

    public function handover(int $id, InventoryHandoverRequest $request)
    {
        $loan = $this->inventoryLoanRepository->findById($id);

        $this->inventoryLoanService->handover($loan, $request->validated());

        return redirect()->route('admin.inventory.loans.show', $id)->with('success', 'Handover berhasil dicatat');
    }

    public function returnItem(int $id, InventoryReturnRequest $request)
    {
        $loan = $this->inventoryLoanRepository->findById($id);

        $this->inventoryLoanService->returnItem($loan, $request->validated());

        return redirect()->route('admin.inventory.loans.show', $id)->with('success', 'Pengembalian berhasil dicatat');
    }

    public function cancel(int $id)
    {
        $loan = $this->inventoryLoanRepository->findById($id);

        $this->inventoryLoanService->cancelLoan($loan);

        return redirect()->route('admin.inventory.loans.show', $id)->with('success', 'Peminjaman berhasil dibatalkan');
    }

    public function storeDocument(int $id, InventoryDocumentRequest $request)
    {
        $loan = $this->inventoryLoanRepository->findById($id);
        $file = $request->file('file');
        $data = $request->validated();

        $document = InventoryDocument::create([
            'inventory_item_id' => $loan->inventory_item_id,
            'inventory_loan_id' => $loan->id,
            'type' => $data['type'],
            'file_path' => $this->fileService->upload($file, 'inventory/docs', 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        if ($data['type'] === InventoryDocument::TYPE_LOAN_AGREEMENT && ! $loan->agreement_document_id) {
            $loan->update(['agreement_document_id' => $document->id]);
        }

        return redirect()->route('admin.inventory.loans.show', $id)->with('success', 'Dokumen berhasil diunggah');
    }

    public function download(int $id)
    {
        $document = InventoryDocument::findOrFail($id);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }
}
