<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Participant;
use App\Repositories\EventParticipantRepository;
use App\Repositories\EventRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventService
{
    public function __construct(
        private EventRepository $eventRepository,
        private EventParticipantRepository $eventParticipantRepository,
        private MembershipService $membershipService,
        private QRCodeService $qrCodeService,
        private NotificationService $notificationService,
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
            } elseif (! $event->price || $event->price == 0) {
                $registrationType = 'free';
                $amount = 0;
            }

            $registration = $this->eventParticipantRepository->create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'registration_type' => $registrationType,
                'amount' => $amount,
                'payment_status' => $amount > 0 ? 'pending' : 'confirmed',
                'is_membership_free' => $isMembershipFree,
                'payment_id' => $paymentId,
            ]);

            $this->qrCodeService->generate($registration);

            $participant->increment('total_events_participated');

            $this->notificationService->notifyAdmins(
                'Peserta baru mendaftar',
                $participant->name.' mendaftar di event '.$event->title.'.',
                'user-plus',
                route('admin.events.show', $event->id),
            );

            $this->notificationService->notifyParticipant(
                $participant,
                'Pendaftaran berhasil',
                'Anda berhasil mendaftar di event '.$event->title.'.',
                'check',
            );
        });
    }

    public function cancelRegistration(Event $event, Participant $participant): void
    {
        DB::transaction(function () use ($event, $participant) {
            $registration = $this->eventParticipantRepository->findByEventAndParticipant(
                $event->id, $participant->id
            );

            if (! $registration) {
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

        $this->notificationService->notifyAdmins(
            'Event dipublikasikan',
            'Event '.$event->title.' telah dipublikasikan.',
            'megaphone',
            route('admin.events.show', $event->id),
        );
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
