<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookkeepingRequest;
use App\Repositories\BookkeepingRepository;
use App\Repositories\EventRepository;
use App\Repositories\SponsorRepository;

class BookkeepingController extends Controller
{
    public function __construct(
        private BookkeepingRepository $bookkeepingRepository,
        private SponsorRepository $sponsorRepository,
        private EventRepository $eventRepository,
    ) {}

    public function index()
    {
        $filters = request()->only(['type', 'category', 'sponsor_id', 'event_id']);
        $entries = $this->bookkeepingRepository->filtered($filters);
        $totals = $this->bookkeepingRepository->totals($filters);
        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->all();

        return view('bookkeepings.index', compact('entries', 'totals', 'filters', 'sponsors', 'events'));
    }

    public function create()
    {
        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->all();

        return view('bookkeepings.create', compact('sponsors', 'events'));
    }

    public function store(BookkeepingRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $data['receipt'] = ImageHelper::upload($request->file('receipt'), 'bookkeepings');
        }

        $this->bookkeepingRepository->create($data);

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil ditambahkan');
    }

    public function show(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id, ['sponsor', 'event', 'createdBy']);

        return view('bookkeepings.show', compact('entry'));
    }

    public function edit(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);
        $sponsors = $this->sponsorRepository->findActive();
        $events = $this->eventRepository->all();

        return view('bookkeepings.edit', compact('entry', 'sponsors', 'events'));
    }

    public function update(int $id, BookkeepingRequest $request)
    {
        $entry = $this->bookkeepingRepository->findById($id);
        $data = $request->validated();

        if ($data['category'] !== 'sponsor') {
            $data['sponsor_id'] = null;
        }

        if ($data['category'] !== 'event_income') {
            $data['event_id'] = null;
        }

        if ($request->hasFile('receipt')) {
            if ($entry->receipt) {
                ImageHelper::delete($entry->receipt);
            }
            $data['receipt'] = ImageHelper::upload($request->file('receipt'), 'bookkeepings');
        }

        $this->bookkeepingRepository->update($entry, $data);

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $entry = $this->bookkeepingRepository->findById($id);

        if ($entry->receipt) {
            ImageHelper::delete($entry->receipt);
        }

        $this->bookkeepingRepository->delete($entry);

        return redirect()->route('admin.bookkeepings.index')->with('success', 'Pembukuan berhasil dihapus');
    }
}
