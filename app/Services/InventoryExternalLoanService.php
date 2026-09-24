<?php

namespace App\Services;

use App\Models\InventoryExternalHandover;
use App\Models\InventoryExternalLoan;
use App\Repositories\InventoryExternalLoanRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryExternalLoanService
{
    public function __construct(
        private InventoryExternalLoanRepository $inventoryExternalLoanRepository,
        private UserService $userService,
        private NotificationService $notificationService,
    ) {}

    public function createLoan(array $data): InventoryExternalLoan
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();

            $data['status'] = InventoryExternalLoan::STATUS_APPROVED;
            $data['created_by'] = $user?->id;
            $data['approved_by'] = $user?->id;
            $data['approved_at'] = now();

            $loan = $this->inventoryExternalLoanRepository->create($data);

            $this->log('create_inventory_external_loan', $loan);
            $this->notificationService->notifyRoles(
                config('sh3.inventory_manage_roles'),
                'Pinjaman barang baru',
                'Meminjam dari '.$loan->external_party.'.',
                'bell',
                route('admin.inventory.external-loans.show', $loan->id),
            );

            return $loan;
        });
    }

    public function handover(InventoryExternalLoan $loan, array $data): InventoryExternalHandover
    {
        return DB::transaction(function () use ($loan, $data) {
            $loan = InventoryExternalLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! $loan->canHandover()) {
                throw ValidationException::withMessages([
                    'status' => ['Handover hanya bisa dicatat untuk pinjaman approved atau borrowed.'],
                ]);
            }

            $isFirst = $loan->status === InventoryExternalLoan::STATUS_APPROVED;

            if ($isFirst) {
                $loan->update([
                    'status' => InventoryExternalLoan::STATUS_BORROWED,
                    'condition_before' => $data['condition_at_handover'],
                    'borrow_date' => $loan->borrow_date ?? now()->toDateString(),
                ]);
            }

            $sequence = (int) $loan->handovers()->max('sequence') + 1;

            $handover = $loan->handovers()->create([
                'sequence' => $sequence,
                'from_location' => $data['from_location'],
                'to_name' => $data['to_name'] ?? null,
                'sender_user_id' => auth()->id(),
                'receiver_user_id' => $data['receiver_user_id'] ?? null,
                'receiver_name' => $data['receiver_name'] ?? null,
                'condition_at_handover' => $data['condition_at_handover'],
                'handed_over_at' => $data['handed_over_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->log('handover_inventory_external_loan', $loan);

            return $handover;
        });
    }

    public function returnItem(InventoryExternalLoan $loan, array $data): InventoryExternalLoan
    {
        return DB::transaction(function () use ($loan, $data) {
            $loan = InventoryExternalLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! $loan->canReturn()) {
                throw ValidationException::withMessages([
                    'status' => ['Pengembalian hanya bisa dilakukan untuk pinjaman yang sedang berjalan.'],
                ]);
            }

            $loan->update([
                'status' => InventoryExternalLoan::STATUS_RETURNED,
                'condition_after' => $data['condition_after'],
                'actual_return_date' => $data['actual_return_date'] ?? now()->toDateString(),
                'returned_received_by' => auth()->id(),
            ]);

            $this->log('return_inventory_external_loan', $loan);

            return $loan;
        });
    }

    public function cancelLoan(InventoryExternalLoan $loan): InventoryExternalLoan
    {
        return DB::transaction(function () use ($loan) {
            $loan = InventoryExternalLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! $loan->canCancel()) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya pinjaman berstatus approved yang bisa dibatalkan.'],
                ]);
            }

            $loan->update(['status' => InventoryExternalLoan::STATUS_CANCELLED]);

            $this->log('cancel_inventory_external_loan', $loan);

            return $loan;
        });
    }

    private function log(string $action, InventoryExternalLoan $loan): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->userService->logActivity($user, $action, [
            'inventory_external_loan_id' => $loan->id,
            'status' => $loan->status,
        ]);
    }
}
