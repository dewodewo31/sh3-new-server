<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MembershipPlan;
use App\Services\MembershipPricingService;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MembershipPricingTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(array $attrs = []): MembershipPlan
    {
        return MembershipPlan::create(array_merge([
            'key' => 'test_' . uniqid(),
            'name' => 'Test',
            'description' => 'test',
            'base_event_price' => 25000,
            'discount_percentage' => 10,
            'reference_event_count' => 53,
            'duration' => 1,
            'duration_unit' => 'years',
            'is_active' => true,
            'sort_order' => 0,
        ], $attrs));
    }

    public function test_annual_caps_at_year_end(): void
    {
        $plan = $this->makePlan(['duration' => 1, 'duration_unit' => 'years', 'discount_percentage' => 10, 'reference_event_count' => 53]);
        $period = app(MembershipPricingService::class)->calculatePeriod($plan, '2026-08-14');

        $this->assertEquals('2027-08-14', $period['normal_end_date']);
        $this->assertEquals('2026-12-31', $period['actual_end_date']);
    }

    public function test_half_year_caps_at_year_end(): void
    {
        $plan = $this->makePlan(['duration' => 6, 'duration_unit' => 'months', 'discount_percentage' => 5, 'reference_event_count' => 26]);
        $period = app(MembershipPricingService::class)->calculatePeriod($plan, '2026-08-14');

        $this->assertEquals('2027-02-14', $period['normal_end_date']);
        $this->assertEquals('2026-12-31', $period['actual_end_date']);
    }

    public function test_weekly_caps_at_year_end(): void
    {
        $plan = $this->makePlan(['duration' => 7, 'duration_unit' => 'days', 'discount_percentage' => 5, 'reference_event_count' => 1]);
        $period = app(MembershipPricingService::class)->calculatePeriod($plan, '2026-12-28');

        $this->assertEquals('2027-01-04', $period['normal_end_date']);
        $this->assertEquals('2026-12-31', $period['actual_end_date']);
    }

    public function test_full_package_price_matches_spec(): void
    {
        $annual = $this->makePlan(['discount_percentage' => 10, 'reference_event_count' => 53]);
        $this->assertEquals(1192500, $annual->fullPackagePrice());

        $half = $this->makePlan(['duration' => 6, 'duration_unit' => 'months', 'discount_percentage' => 5, 'reference_event_count' => 26]);
        $this->assertEquals(617500, $half->fullPackagePrice());

        $weekly = $this->makePlan(['duration' => 7, 'duration_unit' => 'days', 'discount_percentage' => 5, 'reference_event_count' => 1]);
        $this->assertEquals(23750, $weekly->fullPackagePrice());
    }

    public function test_price_uses_actual_sunday_event_count(): void
    {
        $plan = $this->makePlan(['duration' => 1, 'duration_unit' => 'years', 'discount_percentage' => 10, 'reference_event_count' => 53]);

        $start = Carbon::parse('2026-08-14');
        $cursor = $start->copy();
        $sundays = [];
        while (count($sundays) < 3) {
            $cursor->addDay();
            if ($cursor->isSunday()) {
                $sundays[] = $cursor->copy();
            }
        }
        foreach ($sundays as $sunday) {
            Event::factory()->create(['start_date' => $sunday->format('Y-m-d') . ' 07:00:00', 'status' => 'publish']);
        }

        // non-Sunday must not count
        Event::factory()->create(['start_date' => $sundays[0]->copy()->addDay()->format('Y-m-d') . ' 07:00:00', 'status' => 'publish']);

        // Sunday but cancelled must not count
        do {
            $cursor->addDay();
        } while (! $cursor->isSunday());
        Event::factory()->create(['start_date' => $cursor->format('Y-m-d') . ' 07:00:00', 'status' => 'cancelled']);

        $price = app(MembershipPricingService::class)->calculatePrice($plan, '2026-08-14');

        $this->assertEquals(3, $price['eligible_event_count']);
        $this->assertEquals(3 * 22500, $price['final_price']);
    }

    public function test_plan_price_is_stored_as_is_not_recalculated(): void
    {
        // price is admin-defined final price: must NOT be overwritten by the
        // formula (fullPackagePrice() stays available as informational only).
        $plan = $this->makePlan(['price' => 1500000, 'discount_percentage' => 10, 'reference_event_count' => 53]);

        $this->assertSame(1500000, $plan->fresh()->price);
        $this->assertSame(1192500, $plan->fullPackagePrice()); // formula unchanged as info
    }

    public function test_service_plans_returns_stored_price(): void
    {
        $this->makePlan(['key' => 'tahunan', 'price' => 1500000, 'discount_percentage' => 10, 'reference_event_count' => 53]);

        $plans = app(MembershipService::class)->plans();
        $annual = collect($plans)->firstWhere('type', 'tahunan');

        $this->assertNotNull($annual);
        $this->assertEquals(1500000, $annual['price']);
    }
}
