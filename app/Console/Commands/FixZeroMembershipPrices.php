<?php

namespace App\Console\Commands;

use App\Models\MembershipHistory;
use App\Models\MembershipPlan;
use Illuminate\Console\Command;

class FixZeroMembershipPrices extends Command
{
    protected $signature = 'membership:fix-zero-prices';

    protected $description = 'Repair legacy membership_histories with price=0 where the plan has a valid non-zero price. Idempotent; skips histories whose plan price is 0 (valid free membership) and histories already carrying a price.';

    public function handle(): int
    {
        $plans = MembershipPlan::pluck('price', 'key');

        $zero = MembershipHistory::where('price', 0)->get();

        $repaired = 0;
        $free = 0;
        $noPlan = 0;

        foreach ($zero as $history) {
            $planPrice = $plans[$history->membership_type] ?? null;

            if ($planPrice === null) {
                $noPlan++;
                continue;
            }

            if ($planPrice <= 0) {
                $free++; // plan is genuinely free — keep price 0
                continue;
            }

            $history->update(['price' => $planPrice]);
            $repaired++;
        }

        $this->info("{$repaired} membership history(ies) repaired (price=0 -> plan price).");
        $this->info("{$free} left as 0 (plan price is 0 / free membership).");
        $this->info("{$noPlan} skipped (no matching plan found).");

        return self::SUCCESS;
    }
}