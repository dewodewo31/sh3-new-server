<?php

namespace App\Services;

use App\Models\MembershipHistory;
use App\Models\Participant;
use Carbon\Carbon;

/**
 * Phase 2 — Resolves the flat point rate for a participant's VALID check-in.
 *
 * The rate is derived from the participant's ACTIVE membership *history* at the
 * moment of check-in. The definition of "active" is intentionally IDENTICAL to
 * MembershipService::checkEligibility() (audit finding M4): status=active AND
 * end_date >= date — it does NOT use start_date, and it is NEVER read from
 * participants.membership_type (that is a denormalized cache, not authoritative
 * — see H2/M3). The resolved rate is snapshotted into the ledger row at EARN
 * time so later plan edits are never retroactive.
 */
class PointRateService
{
    /**
     * @return array{plan: \App\Models\MembershipPlan|null, rate: int, history: \App\Models\MembershipHistory|null}
     */
    public function resolveAt(Participant $participant, Carbon $date): array
    {
        // Mirrors MembershipService::checkEligibility() exactly so that point
        // earning and membership eligibility can never disagree (audit M4).
        $history = $participant->membershipHistories()
            ->where('status', MembershipHistory::STATUS_ACTIVE)
            ->where('end_date', '>=', $date)
            ->latest()
            ->first();

        if (! $history) {
            return [
                'plan' => null,
                'rate' => $this->nonMemberRate(),
                'history' => null,
            ];
        }

        $plan = $history->plan;

        return [
            'plan' => $plan,
            'rate' => (int) ($plan?->point_per_event_checkin ?? 0),
            'history' => $history,
        ];
    }

    public function nonMemberRate(): int
    {
        return (int) config('points.non_member_rate', 0);
    }
}
