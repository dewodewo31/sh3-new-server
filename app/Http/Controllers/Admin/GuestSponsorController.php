<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestSponsorQuotaRequest;
use App\Http\Requests\GuestSponsorRequest;
use App\Repositories\EventRepository;
use App\Repositories\GuestSponsorRepository;
use App\Repositories\SponsorRepository;
use App\Repositories\UserRepository;
use App\Services\GuestSponsorService;

class GuestSponsorController extends Controller
{
    public function __construct(
        private GuestSponsorService $guestSponsorService,
        private GuestSponsorRepository $guestSponsorRepository,
        private SponsorRepository $sponsorRepository,
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
    ) {}

    public function index()
    {
        $entries = $this->guestSponsorRepository->paginateSorted(
            ['id', 'valid_until', 'is_active', 'created_at'],
            15,
            ['user', 'sponsor', 'event'],
        );

        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->findScannable();

        return view('guest-sponsors.index', [
            'entries' => $entries,
            'sponsors' => $sponsors,
            'events' => $events,
            'totalAccounts' => $this->guestSponsorRepository->countAll(),
            'activeAccounts' => $this->guestSponsorRepository->countActive(),
            'expiredAccounts' => $this->guestSponsorRepository->countExpired(),
            'quotas' => $this->guestSponsorRepository->quotas(),
        ]);
    }

    public function create()
    {
        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->findScannable();

        return view('guest-sponsors.create', compact('sponsors', 'events'));
    }

    public function store(GuestSponsorRequest $request)
    {
        $account = $this->guestSponsorService->createAccount($request->validated(), auth()->id());

        return redirect()
            ->route('admin.guest-sponsors.show', $account->id)
            ->with('success', 'Akun guest sponsor berhasil dibuat')
            ->with('new_username', $account->username)
            ->with('new_password', $account->plain_password);
    }

    public function show(int $id)
    {
        $guestSponsor = $this->guestSponsorRepository->findById($id, ['user', 'sponsor', 'event', 'createdBy']);

        return view('guest-sponsors.show', compact('guestSponsor'));
    }

    public function update(int $id, GuestSponsorRequest $request)
    {
        $guestSponsor = $this->guestSponsorRepository->findById($id, ['user']);
        $data = $request->validated();

        $this->guestSponsorRepository->update($guestSponsor, [
            'valid_from' => $data['valid_from'] ?? $guestSponsor->valid_from?->toDateString(),
            'valid_until' => $data['valid_until'] ?? $guestSponsor->valid_until?->toDateString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        $userData = [];
        if (! empty($data['name'])) {
            $userData['name'] = $data['name'];
        }
        if (! empty($data['password'])) {
            $userData['password'] = $data['password'];
        }
        if ($userData) {
            $this->userRepository->update($guestSponsor->user, $userData);
        }

        return redirect()
            ->route('admin.guest-sponsors.show', $id)
            ->with('success', 'Akun guest sponsor berhasil diupdate')
            ->with('new_password', $data['password'] ?? null);
    }

    public function destroy(int $id)
    {
        $guestSponsor = $this->guestSponsorRepository->findById($id, ['user']);

        $this->guestSponsorRepository->delete($guestSponsor);
        $this->userRepository->delete($guestSponsor->user);

        return redirect()->route('admin.guest-sponsors.index')->with('success', 'Akun guest sponsor berhasil dihapus');
    }

    public function toggleActive(int $id)
    {
        $guestSponsor = $this->guestSponsorRepository->findById($id);

        $this->guestSponsorService->toggleActive($guestSponsor);

        return back()->with('success', 'Status akun guest sponsor diperbarui');
    }

    public function setQuota(GuestSponsorQuotaRequest $request)
    {
        $data = $request->validated();

        $this->guestSponsorService->setQuota($data['sponsor_id'], $data['event_id'], $data['max_guest_accounts']);

        return back()->with('success', 'Kuota guest sponsor berhasil diatur');
    }
}
