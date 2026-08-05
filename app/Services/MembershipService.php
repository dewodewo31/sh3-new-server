<?php

namespace App\Services;

use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use App\Models\Participant;
use App\Repositories\MembershipHistoryRepository;
use App\Repositories\MembershipPlanRepository;
use App\Repositories\ParticipantRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    public function __construct(
        private ParticipantRepository $participantRepository,
        private MembershipHistoryRepository $membershipHistoryRepository,
        private MembershipPlanRepository $membershipPlanRepository,
        private PaymentService $paymentService,
        private NotificationService $notificationService,
    ) {}

    public function checkEligibility(Participant $participant): string
    {
        if ($participant->membership_type === 'none') {
            return 'paid';
        }

        if ($participant->membership_end_date && $participant->membership_end_date >= now()->toDateString()) {
            return 'free';
        }

        return 'paid';
    }

    public function findPlan(string $type): ?MembershipPlan
    {
        return $this->membershipPlanRepository->findActiveByKey($type);
    }

    public function calculatePrice(string $type): int
    {
        return $this->findPlan($type)?->price ?? 0;
    }

    public function calculateEndDate(string $type, ?int $durationMonths = null): Carbon
    {
        if ($durationMonths) {
            return now()->addMonths($durationMonths);
        }

        $plan = $this->findPlan($type);

        if (! $plan) {
            return now();
        }

        return $plan->duration_unit === 'days'
            ? now()->addDays($plan->duration)
            : now()->addMonths($plan->duration);
    }

    public function plans(): array
    {
        return $this->membershipPlanRepository->activePlans()
            ->map(fn (MembershipPlan $plan) => [
                'id' => $plan->id,
                'type' => $plan->key,
                'name' => $plan->name,
                'description' => $plan->description,
                'duration' => $plan->durationLabel(),
                'duration_value' => $plan->duration,
                'duration_unit' => $plan->duration_unit,
                'price' => $plan->price,
            ])
            ->values()
            ->all();
    }

    public function grant(Participant $participant, string $type, ?int $durationMonths = null): MembershipHistory
    {
        return DB::transaction(function () use ($participant, $type, $durationMonths) {
            $this->cancelActiveHistories($participant);

            $startDate = now()->toDateString();
            $endDate = $this->calculateEndDate($type, $durationMonths)->toDateString();

            $history = $participant->membershipHistories()->create([
                'membership_type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $this->calculatePrice($type),
                'status' => MembershipHistory::STATUS_ACTIVE,
            ]);

            $participant->update([
                'membership_type' => $type,
                'membership_start_date' => $startDate,
                'membership_end_date' => $endDate,
            ]);

            $this->notificationService->notifyParticipant(
                $participant,
                'Membership aktif',
                'Membership '.$type.' Anda aktif sampai '.$endDate.'.',
                'badge-check',
            );

            return $history;
        });
    }

    public function requestSubscription(Participant $participant, string $type, string $paymentMethod = 'transfer', ?string $paymentProof = null, ?int $durationMonths = null): MembershipHistory
    {
        return DB::transaction(function () use ($participant, $type, $paymentMethod, $paymentProof, $durationMonths) {
            $price = $this->calculatePrice($type);

            $history = $participant->membershipHistories()->create([
                'membership_type' => $type,
                'start_date' => now()->toDateString(),
                'end_date' => $this->calculateEndDate($type, $durationMonths)->toDateString(),
                'price' => $price,
                'status' => MembershipHistory::STATUS_PENDING,
            ]);

            $this->paymentService->createPayment([
                'participant_id' => $participant->id,
                'payment_type' => 'membership',
                'paymentable_type' => MembershipHistory::class,
                'paymentable_id' => $history->id,
                'amount' => $price,
                'payment_method' => $paymentMethod,
                'payment_proof' => $paymentProof,
                'status' => 'pending',
            ]);

            return $history;
        });
    }

    public function activate(MembershipHistory $history): void
    {
        DB::transaction(function () use ($history) {
            $participant = $history->participant;
            $type = $history->membership_type;

            $this->cancelActiveHistories($participant, $history->id);

            $startDate = now()->toDateString();
            $endDate = $this->calculateEndDate($type)->toDateString();

            $history->update([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => MembershipHistory::STATUS_ACTIVE,
            ]);

            $participant->update([
                'membership_type' => $type,
                'membership_start_date' => $startDate,
                'membership_end_date' => $endDate,
            ]);
        });
    }

    public function cancelMembership(Participant $participant): void
    {
        DB::transaction(function () use ($participant) {
            $participant->update([
                'membership_type' => 'none',
                'membership_start_date' => null,
                'membership_end_date' => null,
            ]);

            $participant->membershipHistories()
                ->whereIn('status', [MembershipHistory::STATUS_ACTIVE, MembershipHistory::STATUS_PENDING])
                ->update(['status' => MembershipHistory::STATUS_CANCELLED]);
        });
    }

    public function cancelHistory(MembershipHistory $history): void
    {
        DB::transaction(function () use ($history) {
            if ($history->status === MembershipHistory::STATUS_ACTIVE) {
                $this->cancelMembership($history->participant);
            } else {
                $history->update(['status' => MembershipHistory::STATUS_CANCELLED]);
            }
        });
    }

    public function markExpiredHistories(): int
    {
        return $this->membershipHistoryRepository->markExpired();
    }

    public function autoRenewal(Participant $participant): void
    {
        $daysUntilExpiry = now()->diffInDays($participant->membership_end_date, false);

        if ($daysUntilExpiry <= 7 && $daysUntilExpiry >= 0) {
            $this->grant($participant, $participant->membership_type);
        }
    }

    public function stats(): array
    {
        return [
            'total' => $this->membershipHistoryRepository->count(),
            'active' => $this->membershipHistoryRepository->countByStatus(MembershipHistory::STATUS_ACTIVE),
            'pending' => $this->membershipHistoryRepository->countByStatus(MembershipHistory::STATUS_PENDING),
            'expired' => $this->membershipHistoryRepository->countByStatus(MembershipHistory::STATUS_EXPIRED),
            'expiring_soon' => $this->membershipHistoryRepository->countExpiringSoon(7),
            'revenue' => $this->membershipHistoryRepository->sumPriceByStatus(MembershipHistory::STATUS_ACTIVE),
        ];
    }

    private function cancelActiveHistories(Participant $participant, ?int $exceptId = null): void
    {
        $participant->membershipHistories()
            ->where('status', MembershipHistory::STATUS_ACTIVE)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['status' => MembershipHistory::STATUS_CANCELLED]);
    }
}
