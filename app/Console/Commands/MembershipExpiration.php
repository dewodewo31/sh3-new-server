<?php

namespace App\Console\Commands;

use App\Services\MembershipService;
use Illuminate\Console\Command;

class MembershipExpiration extends Command
{
    protected $signature = 'membership:expire';
    protected $description = 'Mark expired membership histories as expired';

    public function handle(MembershipService $membershipService): int
    {
        $this->info('Marking expired memberships...');

        $count = $membershipService->markExpiredHistories();

        $this->info("{$count} memberships marked as expired.");

        return self::SUCCESS;
    }
}