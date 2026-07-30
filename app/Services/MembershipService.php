<?php

namespace App\Services;

use App\Models\Participant;
use App\Repositories\ParticipantRepository;
use App\Repositories\MembershipHistoryRepository;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    private array $prices = [
        'tahunan' => 500000,
        'setengah_tahun' => 300000,
        'mingguan' => 50000,
    ];

    public function __construct(
        private ParticipantRepository $participantRepository,
        private ?MembershipHistoryRepository $membershipHistoryRepository = null
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

    public function calculatePrice(string $type): int
    {
        return $this->prices[$type] ?? 0;
    }

    public function subscribe(Participant $participant, string $type, int $durationMonths = 12): Participant
    {
        return DB::transaction(function () use ($participant, $type, $durationMonths) {
            $startDate = now();
            $endDate = now()->addMonths($durationMonths);
            $price = $this->calculatePrice($type);

            $participant->update([
                'membership_type' => $type,
                'membership_start_date' => $startDate,
                'membership_end_date' => $endDate,
            ]);

            $participant->membershipHistories()->create([
                'membership_type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'price' => $price,
                'status' => 'active',
            ]);

            return $participant->fresh();
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
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);
        });
    }

    public function autoRenewal(Participant $participant): void
    {
        $daysUntilExpiry = now()->diffInDays($participant->membership_end_date, false);

        if ($daysUntilExpiry <= 7 && $daysUntilExpiry >= 0) {
            $this->subscribe(
                $participant,
                $participant->membership_type,
                12
            );
        }
    }
}
