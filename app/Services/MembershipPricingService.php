<?php

namespace App\Services;

use App\Models\Event;
use App\Models\MembershipPlan;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for membership pricing & period calculation.
 *
 * Rules (from spec):
 * - MembershipPlan holds the pricing rules (base event price, discount, reference count).
 * - Membership is a computed transaction: price = eligibleSundayEventCount * base * (1 - disc/100).
 * - All memberships end on December 31 of the start year (year-end boundary).
 * - Money is integer Rupiah. No floating point for amounts.
 * - Event statuses that count: publish, ongoing, completed (not draft, not cancelled).
 */
class MembershipPricingService
{
    /** @var string[] statuses that count as a real, running event */
    private const ELIGIBLE_EVENT_STATUSES = ['publish', 'ongoing', 'completed'];

    public function effectiveEventPrice(MembershipPlan $plan): int
    {
        return (int) round($plan->base_event_price * (100 - $plan->discount_percentage) / 100);
    }

    public function fullPackagePrice(MembershipPlan $plan): int
    {
        return $this->effectiveEventPrice($plan) * (int) $plan->reference_event_count;
    }

    /**
     * Period with the global year-end boundary.
     * actual_end = min(normal_end, December 31 of the start year).
     *
     * @return array{start_date:string,normal_end_date:string,year_end_date:string,actual_end_date:string}
     */
    public function calculatePeriod(MembershipPlan $plan, mixed $startDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();

        $normalEnd = match ($plan->duration_unit) {
            'days'   => $start->copy()->addDays($plan->duration),
            'months' => $start->copy()->addMonths($plan->duration),
            'years'  => $start->copy()->addYears($plan->duration),
            default  => $start->copy()->addMonths($plan->duration),
        };

        $yearEnd = Carbon::create($start->year, 12, 31)->startOfDay();
        $actualEnd = $normalEnd->lt($yearEnd) ? $normalEnd : $yearEnd;

        return [
            'start_date'      => $start->toDateString(),
            'normal_end_date' => $normalEnd->toDateString(),
            'year_end_date'   => $yearEnd->toDateString(),
            'actual_end_date' => $actualEnd->toDateString(),
        ];
    }

    public function countEligibleSundayEvents(string $startDate, string $endDate): int
    {
        return Event::query()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->whereRaw('DAYOFWEEK(start_date) = 1') // 1 = Sunday in MySQL
            ->whereIn('status', self::ELIGIBLE_EVENT_STATUSES)
            ->count();
    }

    /**
     * Full price breakdown for a membership starting on $startDate.
     *
     * @return array{plan:string,start_date:string,normal_end_date:string,actual_end_date:string,eligible_event_count:int,base_event_price:int,discount_percentage:int,effective_event_price:int,final_price:int}
     */
    public function calculatePrice(MembershipPlan $plan, mixed $startDate = null): array
    {
        $startDate ??= now()->toDateString();
        $period = $this->calculatePeriod($plan, $startDate);
        $eligibleEventCount = $this->countEligibleSundayEvents($period['start_date'], $period['actual_end_date']);
        $effectiveEventPrice = $this->effectiveEventPrice($plan);
        $finalPrice = $effectiveEventPrice * $eligibleEventCount;

        return [
            'plan'                 => $plan->key,
            'start_date'           => $period['start_date'],
            'normal_end_date'      => $period['normal_end_date'],
            'actual_end_date'      => $period['actual_end_date'],
            'eligible_event_count' => $eligibleEventCount,
            'base_event_price'     => (int) $plan->base_event_price,
            'discount_percentage'  => (int) $plan->discount_percentage,
            'effective_event_price' => $effectiveEventPrice,
            'final_price'          => $finalPrice,
        ];
    }
}
