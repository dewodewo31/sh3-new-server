<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Repositories\EventRepository;
use App\Repositories\EventParticipantRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventService
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventParticipantRepository $eventParticipantRepository,
        private MembershipService $membershipService,
    ) {}

    public function registerParticipant(Event $event, Participant $participant, ?int $paymentId = null): void
    {
        DB::transaction(function () use ($event, $participant, $paymentId) {
            if ($event->remainingQuota() <= 0) {
                throw ValidationException::withMessages([
                    'event' => ['Kuota event sudah penuh.'],
                ]);
            }

            $existing = $this->eventParticipantRepository->findByEventAndParticipant(
                $event->id, $participant->id
            );

            if ($existing) {
                throw ValidationException::withMessages([
                    'event' => ['Anda sudah terdaftar di event ini.'],
                ]);
            }

            $isMembershipFree = false;
            $registrationType = 'paid';
            $amount = $event->price;

            if ($event->is_free_for_members && $this->membershipService->checkEligibility($participant) === 'free') {
                $registrationType = 'membership';
                $amount = 0;
                $isMembershipFree = true;
            } elseif (!$event->price || $event->price == 0) {
                $registrationType = 'free';
                $amount = 0;
            }

            $this->eventParticipantRepository->create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'registration_type' => $registrationType,
                'amount' => $amount,
                'payment_status' => $amount > 0 ? 'pending' : 'confirmed',
                'is_membership_free' => $isMembershipFree,
                'payment_id' => $paymentId,
            ]);

            $participant->increment('total_events_participated');
        });
    }

    public function cancelRegistration(Event $event, Participant $participant): void
    {
        DB::transaction(function () use ($event, $participant) {
            $registration = $this->eventParticipantRepository->findByEventAndParticipant(
                $event->id, $participant->id
            );

            if (!$registration) {
                throw ValidationException::withMessages([
                    'event' => ['Pendaftaran tidak ditemukan.'],
                ]);
            }

            $registration->delete();
            $participant->decrement('total_events_participated');
        });
    }

    public function publishEvent(Event $event): void
    {
        if ($event->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => ['Hanya event dengan status draft yang bisa dipublikasi.'],
            ]);
        }

        $event->update(['status' => 'publish']);
    }

    public function updateEventStatus(): void
    {
        $this->eventRepository->model
            ->where('status', 'publish')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->update(['status' => 'ongoing']);

        $this->eventRepository->model
            ->whereIn('status', ['publish', 'ongoing'])
            ->where('end_date', '<', now())
            ->update(['status' => 'completed']);
    }
}
