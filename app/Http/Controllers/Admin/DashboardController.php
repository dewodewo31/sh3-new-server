<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\MembershipHistory;
use App\Models\Participant;
use App\Models\Payment;
use App\Repositories\EventRepository;
use App\Repositories\ParticipantRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private ParticipantRepository $participantRepository,
        private PaymentRepository $paymentRepository,
        private UserRepository $userRepository,
    ) {}

    public function index()
    {
        $stats = [
            'total_events' => $this->eventRepository->count(),
            'total_participants' => $this->participantRepository->count(),
            'total_payments' => $this->paymentRepository->count(),
            'total_users' => $this->userRepository->count(),
            'upcoming_events' => $this->eventRepository->findUpcoming()->count(),
            'pending_payments' => $this->paymentRepository->findPending()->count(),
        ];

        $recentActivity = $this->buildRecentActivity();
        $upcomingEvents = $this->eventRepository->findUpcoming()->take(5);

        return view('dashboard.index', compact('stats', 'recentActivity', 'upcomingEvents'));
    }

    private function buildRecentActivity(): Collection
    {
        $activity = collect();

        Payment::with('participant')
            ->latest()
            ->limit(5)
            ->get()
            ->each(function (Payment $payment) use ($activity) {
                $activity->push([
                    'type' => $payment->status === 'confirmed' ? 'payment_confirmed' : 'payment',
                    'title' => $payment->status === 'confirmed'
                        ? 'Pembayaran <b>dikonfirmasi</b>'
                        : 'Pembayaran <b>baru</b>',
                    'description' => trim(($payment->invoice_number ?? '').' · '.($payment->participant->name ?? '-'), ' ·'),
                    'time' => $payment->paid_at ?? $payment->created_at,
                ]);
            });

        Event::where('status', 'publish')
            ->latest()
            ->limit(5)
            ->get()
            ->each(function (Event $event) use ($activity) {
                $activity->push([
                    'type' => 'event',
                    'title' => 'Event <b>diterbitkan</b>',
                    'description' => $event->title,
                    'time' => $event->created_at,
                ]);
            });

        Participant::latest()
            ->limit(5)
            ->get()
            ->each(function (Participant $participant) use ($activity) {
                $activity->push([
                    'type' => 'participant',
                    'title' => 'Peserta baru <b>terdaftar</b>',
                    'description' => $participant->name,
                    'time' => $participant->created_at,
                ]);
            });

        MembershipHistory::with('participant')
            ->latest()
            ->limit(5)
            ->get()
            ->each(function (MembershipHistory $membership) use ($activity) {
                $activity->push([
                    'type' => 'membership',
                    'title' => 'Membership <b>baru</b>',
                    'description' => trim(
                        (($membership->participant->name ?? '-').' · '.str_replace('_', ' ', ucwords($membership->membership_type))),
                        ' ·'
                    ),
                    'time' => $membership->created_at,
                ]);
            });

        Attendance::with('eventParticipant.participant')
            ->latest('check_in_time')
            ->limit(5)
            ->get()
            ->each(function (Attendance $attendance) use ($activity) {
                $activity->push([
                    'type' => 'attendance',
                    'title' => 'Check-in <b>berhasil</b>',
                    'description' => $attendance->eventParticipant->participant->name ?? '-',
                    'time' => $attendance->check_in_time ?? $attendance->created_at,
                ]);
            });

        return $activity
            ->sortByDesc('time')
            ->take(8)
            ->values();
    }
}
