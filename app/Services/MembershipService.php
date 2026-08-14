<?php

namespace App\Services;

use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use App\Models\Participant;
use App\Repositories\MembershipPlanRepository;
use App\Services\NotificationService;
use App\Services\PaymentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembershipService
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepository,
        private PaymentService $paymentService,
        private NotificationService $notificationService,
        private MembershipPricingService $pricingService,
    ) {}

    /**
     * All active plans for the API / admin / frontend.
     * `price` is the derived full-package price; the real membership price is
     * computed per-transaction by MembershipPricingService::calculatePrice().
     */
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
                'base_event_price' => $plan->base_event_price,
                'discount_percentage' => $plan->discount_percentage,
                'reference_event_count' => $plan->reference_event_count,
            ])
            ->values()
            ->all();
    }

    public function findPlan(string $type): ?MembershipPlan
    {
        return $this->membershipPlanRepository->findByKey($type);
    }

    /**
     * Final membership price for a plan starting today (= eligible Sunday events * effective price).
     * Integer Rupiah.
     */
    public function calculatePrice(string $type, ?string $startDate = null): int
    {
        $plan = $this->findPlan($type);
        if (! $plan) {
            return 0;
        }

        return $this->pricingService->calculatePrice($plan, $startDate ?? now()->toDateString())['final_price'];
    }

    public function calculateEndDate(string $type, ?int $durationMonths = null): Carbon
    {
        $plan = $this->findPlan($type);
        if (! $plan) {
            return now();
        }

        return Carbon::parse($this->pricingService->calculatePeriod($plan, now()->toDateString())['actual_end_date']);
    }

    public function grant(Participant $participant, string $type, ?int $durationMonths = null): MembershipHistory
    {
        return DB::transaction(function () use ($participant, $type) {
            $this->cancelActiveHistories($participant);

            $plan = $this->findPlan($type);
            $breakdown = $plan ? $this->pricingService->calculatePrice($plan) : null;

            $history = $participant->membershipHistories()->create([
                'membership_type' => $type,
                'start_date' => $breakdown['start_date'] ?? now()->toDateString(),
                'end_date' => $breakdown['actual_end_date'] ?? now()->toDateString(),
                'normal_end_date' => $breakdown['normal_end_date'] ?? null,
                'eligible_event_count' => $breakdown['eligible_event_count'] ?? null,
                'base_event_price' => $breakdown['base_event_price'] ?? null,
                'discount_percentage' => $breakdown['discount_percentage'] ?? null,
                'effective_event_price' => $breakdown['effective_event_price'] ?? null,
                'price' => $plan?->price ?? 0, // ponytail: snapshot plan price, not event-based final_price (was 0 when eligibleEventCount=0)
                'status' => MembershipHistory::STATUS_ACTIVE,
            ]);

            $participant->update([
                'membership_type' => $type,
                'membership_start_date' => $history->start_date,
                'membership_end_date' => $history->end_date,
            ]);

            $this->notificationService->notifyParticipant(
                $participant,
                'membership_granted',
                "Membership {$plan?->name} telah aktif hingga {$history->end_date->format('d M Y')}."
            );

            return $history;
        });
    }

    public function requestSubscription(
        Participant $participant,
        string $type,
        string $paymentMethod = 'transfer',
        ?string $paymentProof = null,
        ?int $durationMonths = null
    ): MembershipHistory {
        return DB::transaction(function () use ($participant, $type, $paymentMethod, $paymentProof) {
            $plan = $this->findPlan($type);
            $breakdown = $plan ? $this->pricingService->calculatePrice($plan) : null;
            $price = $plan?->price ?? 0; // ponytail: snapshot plan price, not event-based

            $history = $participant->membershipHistories()->create([
                'membership_type' => $type,
                'start_date' => $breakdown['start_date'] ?? now()->toDateString(),
                'end_date' => $breakdown['actual_end_date'] ?? now()->toDateString(),
                'normal_end_date' => $breakdown['normal_end_date'] ?? null,
                'eligible_event_count' => $breakdown['eligible_event_count'] ?? null,
                'base_event_price' => $breakdown['base_event_price'] ?? null,
                'discount_percentage' => $breakdown['discount_percentage'] ?? null,
                'effective_event_price' => $breakdown['effective_event_price'] ?? null,
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

            $plan = $this->findPlan($type);
            $breakdown = $plan ? $this->pricingService->calculatePrice($plan) : null;

            $history->update([
                'start_date' => $breakdown['start_date'] ?? now()->toDateString(),
                'end_date' => $breakdown['actual_end_date'] ?? now()->toDateString(),
                'normal_end_date' => $breakdown['normal_end_date'] ?? null,
                'eligible_event_count' => $breakdown['eligible_event_count'] ?? null,
                'base_event_price' => $breakdown['base_event_price'] ?? null,
                'discount_percentage' => $breakdown['discount_percentage'] ?? null,
                'effective_event_price' => $breakdown['effective_event_price'] ?? null,
                'price' => $plan?->price ?? $history->price, // ponytail: preserve plan snapshot, don't recompute from events
                'status' => MembershipHistory::STATUS_ACTIVE,
            ]);

            $participant->update([
                'membership_type' => $type,
                'membership_start_date' => $history->start_date,
                'membership_end_date' => $history->end_date,
            ]);

            $this->notificationService->notifyParticipant(
                $participant,
                'membership_activated',
                "Membership {$plan?->name} diaktifkan hingga {$history->end_date->format('d M Y')}."
            );
        });
    }

    public function cancelMembership(Participant $participant, string $reason = 'manual'): void
    {
        DB::transaction(function () use ($participant, $reason) {
            $this->cancelActiveHistories($participant);

            $participant->update([
                'membership_type' => 'none',
                'membership_start_date' => null,
                'membership_end_date' => null,
            ]);

            $this->notificationService->notifyParticipant(
                $participant,
                'membership_cancelled',
                "Membership dibatalkan: {$reason}"
            );
        });
    }

    public function cancelHistory(MembershipHistory $history, string $reason = 'manual'): void
    {
        $history->update(['status' => MembershipHistory::STATUS_CANCELLED]);
    }

    public function checkEligibility(Participant $participant): array
    {
        $active = $participant->membershipHistories()
            ->where('status', MembershipHistory::STATUS_ACTIVE)
            ->where('end_date', '>=', now())
            ->latest()
            ->first();

        return [
            'is_eligible' => ! is_null($active),
            'membership_type' => $active?->membership_type,
            'end_date' => $active?->end_date?->format('Y-m-d'),
        ];
    }

    public function markExpiredHistories(): int
    {
        $expired = MembershipHistory::query()
            ->where('status', MembershipHistory::STATUS_ACTIVE)
            ->where('end_date', '<', now())
            ->get();

        foreach ($expired as $history) {
            $history->update(['status' => MembershipHistory::STATUS_EXPIRED]);

            try {
                $this->notificationService->notifyParticipant(
                    $history->participant,
                    'membership_expired',
                    "Membership {$history->plan?->name} telah berakhir."
                );
            } catch (\Throwable $e) {
                Log::warning("Failed to notify membership expiry for history {$history->id}: {$e->getMessage()}");
            }
        }

        return $expired->count();
    }

    public function autoRenewal(): int
    {
        // Auto-renewal hook — currently disabled, no payment gateway connected.
        return 0;
    }

    public function stats(): array
    {
        $now = now();
        $expiringSoon = $now->copy()->addDays(7);

        return [
            'total' => MembershipHistory::count(),
            'active' => MembershipHistory::where('status', MembershipHistory::STATUS_ACTIVE)->count(),
            'pending' => MembershipHistory::where('status', MembershipHistory::STATUS_PENDING)->count(),
            'expired' => MembershipHistory::where('status', MembershipHistory::STATUS_EXPIRED)->count(),
            'expiring_soon' => MembershipHistory::where('status', MembershipHistory::STATUS_ACTIVE)
                ->whereBetween('end_date', [$now->toDateString(), $expiringSoon->toDateString()])
                ->count(),
            'revenue' => (int) MembershipHistory::whereIn('status', [
                MembershipHistory::STATUS_ACTIVE,
                MembershipHistory::STATUS_EXPIRED,
            ])->sum('price'),
        ];
    }

    private function cancelActiveHistories(Participant $participant, ?int $exceptId = null): void
    {
        $query = $participant->membershipHistories()
            ->where('status', MembershipHistory::STATUS_ACTIVE);

        if ($exceptId) {
            $query->where('id', '<>', $exceptId);
        }

        foreach ($query->get() as $history) {
            $history->update(['status' => MembershipHistory::STATUS_CANCELLED]);
        }
    }
}
