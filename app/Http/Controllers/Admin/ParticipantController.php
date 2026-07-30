<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParticipantRequest;
use App\Repositories\ParticipantRepository;

class ParticipantController extends Controller
{
    public function __construct(
        private ParticipantRepository $participantRepository,
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
        $this->participantRepository->create($request->validated());

        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil ditambahkan');
    }

    public function show(int $id)
    {
        $participant = $this->participantRepository->findById($id, ['membershipHistories', 'eventParticipants.event']);

        return view('participants.show', compact('participant'));
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

        return redirect()->route('admin.participants.index')->with('success', 'Data peserta berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $participant = $this->participantRepository->findById($id);
        $this->participantRepository->delete($participant);

        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil dihapus');
    }
}
