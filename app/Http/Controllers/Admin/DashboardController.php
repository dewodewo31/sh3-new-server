<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\EventRepository;
use App\Repositories\ParticipantRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private EventRepository $eventRepository,
        private ParticipantRepository $participantRepository,
        private PaymentRepository $paymentRepository,
        private UserRepository $userRepository,
        private DashboardService $dashboardService,
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

        $recentActivity = $this->dashboardService->buildRecentActivity();
        $upcomingEvents = $this->eventRepository->findUpcoming()->take(5);

        return view('dashboard.index', compact('stats', 'recentActivity', 'upcomingEvents'));
    }
}
