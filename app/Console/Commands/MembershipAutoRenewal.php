<?php

namespace App\Console\Commands;

use App\Models\Participant;
use App\Services\MembershipService;
use Illuminate\Console\Command;

class MembershipAutoRenewal extends Command
{
    protected $signature = 'membership:auto-renew';
    protected $description = 'Auto-renew memberships expiring within 7 days';

    public function handle(MembershipService $membershipService): int
    {
        $this->info('Checking memberships for auto-renewal...');

        $expiring = Participant::where('membership_type', '!=', 'none')
            ->whereDate('membership_end_date', '<=', now()->addDays(7))
            ->whereDate('membership_end_date', '>=', now()->toDateString())
            ->get();

        $count = 0;

        foreach ($expiring as $participant) {
            $membershipService->autoRenewal($participant);
            $count++;
        }

        $this->info("{$count} memberships auto-renewed.");

        return self::SUCCESS;
    }
}