<?php

namespace App\Services;

use App\Models\InventoryHandover;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Repositories\InventoryLoanRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryLoanService
{
    public function __construct(
        private InventoryLoanRepository $inventoryLoanRepository,
        private UserService $userService,
        private NotificationService $notificationService,
    ) {}

    public function createLoan(array $data): InventoryLoan
    {
        return DB::transaction(function () use ($data) {
            $item = InventoryItem::query()->whereKey($data['inventory_item_id'])->lockForUpdate()->firstOrFail();

            if ($item->status !== InventoryItem::STATUS_AVAILABLE) {
                throw ValidationException::withMessages([
                    'inventory_item_id' => ['Item tidak tersedia untuk dipinjam.'],
                ]);
            }

            $hasActiveLoan = InventoryLoan::query()
                ->where('inventory_item_id', $item->id)
                ->whereIn('status', [InventoryLoan::STATUS_APPROVED, InventoryLoan::STATUS_BORROWED])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveLoan) {
                throw ValidationException::withMessages([
                    'inventory_item_id' => ['Item ini sedang dalam peminjaman aktif.'],
                ]);
            }

            $user = auth()->user();

            $data['status'] = InventoryLoan::STATUS_APPROVED;
            $data['created_by'] = $user?->id;
            $data['approved_by'] = $user?->id;
            $data['approved_at'] = now();

            $loan = $this->inventoryLoanRepository->create($data);
            $item->update(['status' => InventoryItem::STATUS_BORROWED]);

            $this->log('create_inventory_loan', $loan);
            $this->notificationService->notifyRoles(
                config('sh3.inventory_manage_roles'),
                'Peminjaman inventaris baru',
                $loan->borrower_name.' meminjam '.$item->name.'.',
                'bell',
                route('admin.inventory.loans.show', $loan->id),
            );

            return $loan;
        });
    }

    public function handover(InventoryLoan $loan, array $data): InventoryHandover
    {
        return DB::transaction(function () use ($loan, $data) {
            $loan = InventoryLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! $loan->canHandover()) {
                throw ValidationException::withMessages([
                    'status' => ['Handover hanya bisa dicatat untuk peminjaman approved atau borrowed.'],
                ]);
            }

            $item = InventoryItem::query()->whereKey($loan->inventory_item_id)->lockForUpdate()->firstOrFail();
            $isFirst = $loan->status === InventoryLoan::STATUS_APPROVED;

            if ($isFirst) {
                $loan->update([
                    'status' => InventoryLoan::STATUS_BORROWED,
                    'condition_before' => $data['condition_at_handover'],
                    'borrow_date' => $loan->borrow_date ?? now()->toDateString(),
                ]);
                // Item already marked borrowed on createLoan; ensure status sticks.
                if ($item->status !== InventoryItem::STATUS_BORROWED) {
                    $item->update(['status' => InventoryItem::STATUS_BORROWED]);
                }
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

            $this->log('handover_inventory_loan', $loan);

            return $handover;
        });
    }

    public function returnItem(InventoryLoan $loan, array $data): InventoryLoan
    {
        return DB::transaction(function () use ($loan, $data) {
            $loan = InventoryLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! $loan->canReturn()) {
                throw ValidationException::withMessages([
                    'status' => ['Pengembalian hanya bisa dilakukan untuk peminjaman yang sedang berjalan.'],
                ]);
            }

            $item = InventoryItem::query()->whereKey($loan->inventory_item_id)->lockForUpdate()->firstOrFail();

            $loan->update([
                'status' => InventoryLoan::STATUS_RETURNED,
                'condition_after' => $data['condition_after'],
                'actual_return_date' => $data['actual_return_date'] ?? now()->toDateString(),
                'returned_received_by' => auth()->id(),
            ]);
            $item->update(['status' => InventoryItem::STATUS_AVAILABLE]);

            $this->log('return_inventory_loan', $loan);

            return $loan;
        });
    }

    public function cancelLoan(InventoryLoan $loan): InventoryLoan
    {
        return DB::transaction(function () use ($loan) {
            $loan = InventoryLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! $loan->canCancel()) {
                throw ValidationException::withMessages([
                    'status' => ['Hanya peminjaman berstatus approved yang bisa dibatalkan.'],
                ]);
            }

            $item = InventoryItem::query()->whereKey($loan->inventory_item_id)->lockForUpdate()->firstOrFail();

            $loan->update(['status' => InventoryLoan::STATUS_CANCELLED]);
            // Loan was approved (item borrowed); free it on cancel.
            if ($item->status === InventoryItem::STATUS_BORROWED) {
                $item->update(['status' => InventoryItem::STATUS_AVAILABLE]);
            }

            $this->log('cancel_inventory_loan', $loan);

            return $loan;
        });
    }

    private function log(string $action, InventoryLoan $loan): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->userService->logActivity($user, $action, [
            'inventory_loan_id' => $loan->id,
            'inventory_item_id' => $loan->inventory_item_id,
            'status' => $loan->status,
        ]);
    }
}
