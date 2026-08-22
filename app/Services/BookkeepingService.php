<?php

namespace App\Services;

use App\Models\Bookkeeping;
use App\Models\User;
use App\Repositories\BookkeepingRepository;
use Illuminate\Support\Facades\DB;

class BookkeepingService
{
    public function __construct(
        private BookkeepingRepository $repository,
        private UserService $userService,
    ) {}

    public function create(array $data, ?User $user): Bookkeeping
    {
        $status = $data['status'] ?? Bookkeeping::STATUS_DRAFT;

        if (! in_array($status, [
            Bookkeeping::STATUS_DRAFT,
            Bookkeeping::STATUS_SUBMITTED,
            Bookkeeping::STATUS_PAID,
        ], true)) {
            throw new \InvalidArgumentException('New bookkeeping entries may only start as draft, submitted, or paid.');
        }

        $data['status'] = $status;

        if ($status === Bookkeeping::STATUS_PAID) {
            // Recording an already-settled entry: treat as pre-approved.
            $data['approved_by'] = $user?->id;
            $data['approved_at'] = now();
        }

        if ($user && ! isset($data['created_by'])) {
            $data['created_by'] = $user->id;
        }

        return DB::transaction(function () use ($data, $user, $status) {
            $bk = $this->repository->create($data);

            $this->audit($user, 'create_bookkeeping', [
                'bookkeeping_id' => $bk->id,
                'status' => $status,
            ]);

            return $bk;
        });
    }

    public function update(Bookkeeping $bk, array $data, ?User $user): void
    {
        if (! $bk->canBeEdited()) {
            throw new \InvalidArgumentException('Paid bookkeeping entries cannot be edited.');
        }

        // Status is owned by the lifecycle methods (submit/approve/markPaid/cancel).
        unset($data['status']);

        DB::transaction(function () use ($bk, $data, $user) {
            $this->repository->update($bk, $data);

            $this->audit($user, 'update_bookkeeping', [
                'bookkeeping_id' => $bk->id,
            ]);
        });
    }

    public function delete(Bookkeeping $bk, ?User $user): void
    {
        if (! $bk->canBeDeleted()) {
            throw new \InvalidArgumentException('Paid bookkeeping entries cannot be deleted.');
        }

        DB::transaction(function () use ($bk, $user) {
            $this->repository->delete($bk);

            $this->audit($user, 'delete_bookkeeping', [
                'bookkeeping_id' => $bk->id,
            ]);
        });
    }

    public function submit(Bookkeeping $bk, ?User $user): void
    {
        if (! $bk->canSubmit()) {
            throw new \InvalidArgumentException('Only draft entries can be submitted.');
        }

        DB::transaction(function () use ($bk, $user) {
            $this->repository->update($bk, ['status' => Bookkeeping::STATUS_SUBMITTED]);

            $this->audit($user, 'submit_bookkeeping', [
                'bookkeeping_id' => $bk->id,
            ]);
        });
    }

    public function approve(Bookkeeping $bk, User $user): void
    {
        if (! $bk->canApprove()) {
            throw new \InvalidArgumentException('Only draft or submitted entries can be approved.');
        }

        DB::transaction(function () use ($bk, $user) {
            $this->repository->update($bk, [
                'status' => Bookkeeping::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->audit($user, 'approve_bookkeeping', [
                'bookkeeping_id' => $bk->id,
            ]);
        });
    }

    public function markPaid(Bookkeeping $bk, ?User $user): void
    {
        if (! $bk->canMarkPaid()) {
            throw new \InvalidArgumentException('Only approved entries can be marked as paid.');
        }

        DB::transaction(function () use ($bk, $user) {
            $this->repository->update($bk, ['status' => Bookkeeping::STATUS_PAID]);

            $this->audit($user, 'mark_paid_bookkeeping', [
                'bookkeeping_id' => $bk->id,
            ]);
        });
    }

    public function cancel(Bookkeeping $bk, ?User $user): void
    {
        if (! $bk->canCancel()) {
            throw new \InvalidArgumentException('Only draft, submitted, or approved entries can be cancelled; paid entries cannot.');
        }

        DB::transaction(function () use ($bk, $user) {
            $this->repository->update($bk, ['status' => Bookkeeping::STATUS_CANCELLED]);

            $this->audit($user, 'cancel_bookkeeping', [
                'bookkeeping_id' => $bk->id,
            ]);
        });
    }

    private function audit(?User $user, string $action, array $details): void
    {
        if ($user) {
            $this->userService->logActivity($user, $action, $details);
        }
    }
}
