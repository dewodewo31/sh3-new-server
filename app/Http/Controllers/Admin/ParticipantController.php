<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParticipantRequest;
use App\Repositories\ParticipantRepository;
use App\Services\PointService;
use App\Services\UserService;

class ParticipantController extends Controller
{
    public function __construct(
        private ParticipantRepository $participantRepository,
        private PointService $pointService,
        private UserService $userService,
    ) {}

    public function index()
    {
        $participants = $this->participantRepository->paginateWithMembership();

        return view('participants.index', compact('participants'));
    }

    public function create()
    {
        return view('participants.create');
    }

    public function store(ParticipantRequest $request)
    {
        $participant = $this->participantRepository->create($request->validated());

        $this->userService->logActivity(auth()->user(), 'create_participant', ['participant_id' => $participant->id, 'name' => $participant->name]);

        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil ditambahkan');
    }

    public function show(int $id)
    {
        $participant = $this->participantRepository->findById($id, ['membershipHistories.plan', 'eventParticipants.event', 'membershipPlan']);
        $ledgerBalance = $this->pointService->balanceFromLedger($participant->id);
        $pointHistory = $this->pointService->historyForParticipant($participant->id);

        return view('participants.show', compact('participant', 'ledgerBalance', 'pointHistory'));
    }

    public function reconcilePoints(int $id)
    {
        $participant = $this->participantRepository->findById($id);
        $this->pointService->reconcileBalance($participant->id);

        return redirect()->route('admin.participants.show', $participant->id)
            ->with('success', 'Saldo poin disinkronkan dari ledger.');
    }

    public function edit(int $id)
    {
        $participant = $this->participantRepository->findById($id);

        return view('participants.edit', compact('participant'));
    }

    public function update(int $id, ParticipantRequest $request)
    {
        $participant = $this->participantRepository->findById($id);
        $this->participantRepository->update($participant, $request->validated());

        $this->userService->logActivity(auth()->user(), 'update_participant', ['participant_id' => $participant->id, 'name' => $participant->name]);

        return redirect()->route('admin.participants.index')->with('success', 'Data peserta berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $participant = $this->participantRepository->findById($id);
        $this->participantRepository->delete($participant);

        $this->userService->logActivity(auth()->user(), 'delete_participant', ['participant_id' => $id, 'name' => $participant->name]);

        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil dihapus');
    }
}
