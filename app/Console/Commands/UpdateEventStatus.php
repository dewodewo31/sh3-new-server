<?php

namespace App\Console\Commands;

use App\Services\EventService;
use Illuminate\Console\Command;

class UpdateEventStatus extends Command
{
    protected $signature = 'events:update-status';
    protected $description = 'Update event statuses (draft→ongoing→completed) based on dates';

    public function handle(EventService $eventService): int
    {
        $this->info('Updating event statuses...');

        $eventService->updateEventStatus();

        $this->info('Event statuses updated successfully.');

        return self::SUCCESS;
    }
}