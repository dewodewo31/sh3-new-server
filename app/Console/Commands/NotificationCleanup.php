<?php

namespace App\Console\Commands;

use Illuminate\Notifications\DatabaseNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotificationCleanup extends Command
{
    protected $signature = 'notifications:cleanup {--days=30 : Delete notifications older than N days}';
    protected $description = 'Clean up old notifications from the database';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Deleting notifications older than {$days} days ({$cutoff->toDateString()})...");

        $deleted = DatabaseNotification::where('created_at', '<', $cutoff)->delete();

        $this->info("{$deleted} old notifications deleted.");

        return self::SUCCESS;
    }
}