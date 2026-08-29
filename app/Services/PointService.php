<?php

namespace App\Services;

use App\Exceptions\PointReversalBlockedException;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\MerchandiseOrder;
use App\Models\Participant;
use App\Models\PointTransaction;
use App\Repositories\PointTransactionRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Phase 3 — Point ledger orchestrator.
 *
 * The ledger (point_transactions) is APPEND-ONLY: rows are only INSERTed, never
 * UPDATE/DELETE'd. participants.point_balance is a denormalized cache that is
 * always recomputable from the ledger and is kept in sync by these methods.
 *
 * Idempotency is enforced by the UNIQUE(source_type, source_id) database index:
 *   - EARN    source_id = attendance_id          -> at most one EARN per attendance
 *   - REDEEM  source_id = merchandise_order_id   -> at most one deduction per order
 *   - REVERSAL source_id = referenced tx/order   -> a reversal can never double-apply
 *
 * These methods are designed to run INSIDE a caller-owned DB transaction so the
 * point mutation is atomic with the check-in / redemption that triggers it.
 * Balances are guarded so they can never go negative.
 */
class PointService
{
    public const SOURCE_ATTENDANCE = 'attendance';
    public const SOURCE_ORDER = 'merchandise_order';

    public function __construct(
        private PointTransactionRepository $repository,
        private PointRateService $rateService,
    ) {}

    /**
     * Grant flat points for a valid attendance/check-in.
     * Returns null when no points are earned (OTS / aggregator / zero rate).
     * Idempotent per attendance.
     */
    public function earnForAttendance(Attendance $attendance, Event $event, Participant $participant): ?PointTransaction
    {
        // Hard invariant (H1): Manual-OTS aggregator never earns.
        if ($participant->isOtsAggregator()) {
            return null;
        }

        // Hard invariant: member-OTS registrations (qr_code OTS-*) never earn.
        if ($this->isOtsRegistration($attendance->eventParticipant)) {
            return null;
        }

        // Idempotency: a given attendance can only ever produce one EARN.
        $existing = $this->repository->findSource(self::SOURCE_ATTENDANCE, $attendance->id);
        if ($existing) {
            return $existing;
        }

        $resolved = $this->rateService->resolveAt($participant, now());
        $rate = $resolved['rate'];

        if ($rate <= 0) {
            return null;
        }

        $tx = $this->repository->transaction(
            PointTransaction::TYPE_EARN,
            $participant->id,
            $rate,
            [
                'source_type' => self::SOURCE_ATTENDANCE,
                'source_id' => $attendance->id,
                'membership_plan_id' => $resolved['plan']?->id,
                'point_rate' => $rate,
                'event_id' => $event->id,
                'attendance_id' => $attendance->id,
                'ref' => 'attendance#'.$attendance->id,
                'note' => 'Check-in '.($resolved['plan']?->key ?? 'non-member'),
            ]
        );

        $participant->increment('point_balance', $rate);

        return $tx;
    }

    /**
     * Deduct points for a merchandise redemption. Runs inside the redemption
     * transaction (participant row locked FOR UPDATE) so the guard is race-free.
     * Idempotent per order. Throws when the balance is insufficient.
     */
    public function redeemForOrder(Participant $participant, MerchandiseOrder $order, int $points): PointTransaction
    {
        $existing = $this->repository->findSource(self::SOURCE_ORDER, $order->id);
        if ($existing) {
            return $existing;
        }

        if ($participant->point_balance < $points) {
            throw ValidationException::withMessages([
                'points' => ['Poin tidak mencukupi untuk melakukan penukaran merchandise.'],
            ]);
        }

        $tx = $this->repository->transaction(
            PointTransaction::TYPE_REDEEM,
            $participant->id,
            -$points,
            [
                'source_type' => self::SOURCE_ORDER,
                'source_id' => $order->id,
                'merchandise_order_id' => $order->id,
                'ref' => 'order#'.$order->id,
            ]
        );

        $participant->decrement('point_balance', $points);

        return $tx;
    }

    /**
     * Reverse a REDEEM (order cancelled / payment rejected) — returns the spent
     * points. Idempotent per order: only reverses once.
     */
    public function refundRedemption(Participant $participant, MerchandiseOrder $order): ?PointTransaction
    {
        $redeem = $this->repository->findSource(self::SOURCE_ORDER, $order->id);

        if (! $redeem || $redeem->type !== PointTransaction::TYPE_REDEEM) {
            return null;
        }

        if ($this->repository->findSource('reversal_order', $order->id)) {
            return null; // already reversed
        }

        $tx = $this->repository->transaction(
            PointTransaction::TYPE_REVERSAL,
            $participant->id,
            abs($redeem->amount),
            [
                'source_type' => 'reversal_order',
                'source_id' => $order->id,
                'merchandise_order_id' => $order->id,
                'ref' => 'refund-order#'.$order->id,
            ]
        );

        $participant->increment('point_balance', abs($redeem->amount));

        return $tx;
    }

    /**
     * Reverse an EARN when an attendance is invalidated (e.g. the event payment
     * is rejected/refunded). Returns the REVERSAL tx, or null when nothing was
     * earned / nothing to reverse.
     */
    public function reverseEarn(Attendance $attendance, Participant $participant, ?string $reason = null): ?PointTransaction
    {
        $earn = $this->repository->findSource(self::SOURCE_ATTENDANCE, $attendance->id);

        if (! $earn || $earn->type !== PointTransaction::TYPE_EARN) {
            return null;
        }

        if ($this->repository->findSource('reversal_attendance', $attendance->id)) {
            return null; // already reversed (idempotent)
        }

        $earned = abs($earn->amount);

        // Hard invariant (H3 / V2): a reversal must NEVER silently create a
        // negative balance. If reversing would push the balance below zero the
        // caller must NOT clamp — it must surface the condition and route it to
        // the audited admin-adjustment workflow instead.
        if ($participant->point_balance < $earned) {
            throw new PointReversalBlockedException(
                'Reversal dibatalkan: saldo poin ('.$participant->point_balance.') tidak mencukupi untuk membatalkan '.$earned.' poin (perlu penyesuaian admin).'
            );
        }

        $tx = $this->repository->transaction(
            PointTransaction::TYPE_REVERSAL,
            $participant->id,
            -$earned,
            [
                'source_type' => 'reversal_attendance',
                'source_id' => $attendance->id,
                'attendance_id' => $attendance->id,
                'ref' => 'reverse-attendance#'.$attendance->id,
                'note' => $reason,
            ]
        );

        $participant->decrement('point_balance', $earned);

        return $tx;
    }

    /**
     * Audited technical/admin balance adjustment (ADJUSTMENT ledger entry).
     *
     * This is the explicit, append-only, operator-attributed escape hatch used
     * when an automated EARN reversal is BLOCKED (would go negative) or for any
     * other legitimately audited correction. It is NEVER invoked automatically by
     * earning/redemption flows.
     *
     * @param int $amount signed delta (positive = grant, negative = deduct)
     */
    public function adminAdjust(Participant $participant, int $amount, int $adminUserId, string $reason): PointTransaction
    {
        if ($amount === 0) {
            throw ValidationException::withMessages([
                'points' => ['Penyesuaian harus bernilai bukan nol.'],
            ]);
        }

        // Even explicit admin adjustments must respect the non-negative invariant.
        if ($participant->point_balance + $amount < 0) {
            throw ValidationException::withMessages([
                'points' => ['Penyesuaian akan membuat saldo poin negatif.'],
            ]);
        }

        $tx = $this->repository->transaction(
            PointTransaction::TYPE_ADJUSTMENT,
            $participant->id,
            $amount,
            [
                'adjusted_by' => $adminUserId,
                'ref' => 'adjustment#'.$participant->id,
                'note' => $reason,
            ]
        );

        if ($amount > 0) {
            $participant->increment('point_balance', $amount);
        } else {
            $participant->decrement('point_balance', abs($amount));
        }

        return $tx;
    }

    public function balanceFromLedger(int $participantId): int
    {
        return $this->repository->balanceFromLedger($participantId);
    }

    public function historyForParticipant(int $participantId, int $perPage = 15)
    {
        return $this->repository->historyForParticipant($participantId, $perPage);
    }

    /**
     * Recompute participants.point_balance from the immutable ledger.
     * Safe because the ledger is the source of truth; the denormalized cache
     * column may drift and is always recomputable. No ledger row is written.
     */
    public function reconcileBalance(int $participantId): int
    {
        $ledger = $this->balanceFromLedger($participantId);
        $participant = Participant::findOrFail($participantId);
        $participant->point_balance = $ledger;
        $participant->save();

        return $ledger;
    }

    /**
     * Given an attendee's EventParticipant (may be null / detached), detect an
     * OTS registration by the QR prefix.
     */
    private function isOtsRegistration($eventParticipant): bool
    {
        if (! $eventParticipant) {
            return false;
        }

        $qr = $eventParticipant->qr_code;

        return is_string($qr) && str_starts_with($qr, 'OTS-');
    }
}
