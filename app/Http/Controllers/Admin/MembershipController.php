<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MembershipRequest;
use App\Repositories\MembershipHistoryRepository;
use App\Repositories\ParticipantRepository;
use App\Services\MembershipService;

class MembershipController extends Controller
{
    public function __construct(
        private MembershipHistoryRepository $membershipHistoryRepository,
        private MembershipService $membershipService,
        private ParticipantRepository $participantRepository,
    ) {}

    public function index()
    {
        $this->membershipService->markExpiredHistories();

        $histories = $this->membershipHistoryRepository->paginateWithParticipant();
        $stats = $this->membershipService->stats();

        return view('memberships.index', compact('histories', 'stats'));
    }

    public function create()
    {
        $participants = $this->participantRepository->all(['membershipPlan']);
        $plans = $this->membershipService->plans();

        return view('memberships.create', compact('participants', 'plans'));
    }

    public function store(MembershipRequest $request)
    {
        $participant = $this->participantRepository->findById($request->integer('participant_id'));

        $this->membershipService->grant(
            $participant,
            $request->string('membership_type')->toString(),
            $request->integer('duration_months') ?: null,
        );

        return redirect()->route('admin.memberships.index')->with('success', 'Membership berhasil diberikan kepada '.$participant->name);
    }

    public function cancel(int $id)
    {
        $history = $this->membershipHistoryRepository->findById($id, ['participant']);

        $this->membershipService->cancelHistory($history);

        return redirect()->route('admin.memberships.index')->with('success', 'Membership berhasil dibatalkan');
    }
}
