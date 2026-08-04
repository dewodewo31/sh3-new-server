<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Repositories\CategoryRepository;
use App\Repositories\EventRepository;
use App\Services\UserService;

class EventController extends Controller
{
        public function __construct(
        private EventRepository $eventRepository,
        private CategoryRepository $categoryRepository,
        private \App\Services\EventService $eventService,
        private UserService $userService,
    ) {}

    public function index()
    {
        $filters = request()->only(['search', 'category_id', 'status']);
        $events = $this->eventRepository->search($filters);
        $categories = $this->categoryRepository->findActive();

        return view('events.index', compact('events', 'categories', 'filters'));
    }

    public function create()
    {
        $categories = $this->categoryRepository->findActive();

        return view('events.create', compact('categories'));
    }

    public function store(EventRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        foreach (['image', 'banner'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = ImageHelper::upload($request->file($field), 'events');
            }
        }

        $event = $this->eventRepository->create($data);

        $this->userService->logActivity(auth()->user(), 'create_event', ['event_id' => $event->id, 'title' => $event->title]);

        return redirect()->route('admin.events.index')->with('success', 'Event berhasil dibuat');
    }

    public function show(int $id)
    {
        $event = $this->eventRepository->findById($id, ['category', 'schedules', 'eventParticipants.participant']);

        return view('events.show', compact('event'));
    }

    public function edit(int $id)
    {
        $event = $this->eventRepository->findById($id);
        $categories = $this->categoryRepository->findActive();

        return view('events.edit', compact('event', 'categories'));
    }

    public function update(int $id, EventRequest $request)
    {
        $event = $this->eventRepository->findById($id);
        $data = $request->validated();
        $data['updated_by'] = auth()->id();

        foreach (['image', 'banner'] as $field) {
            if ($request->hasFile($field)) {
                if ($event->{$field}) {
                    ImageHelper::delete($event->{$field});
                }
                $data[$field] = ImageHelper::upload($request->file($field), 'events');
            }
        }

        $this->eventRepository->update($event, $data);

        $this->userService->logActivity(auth()->user(), 'update_event', ['event_id' => $event->id, 'title' => $event->title]);

        return redirect()->route('admin.events.index')->with('success', 'Event berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $event = $this->eventRepository->findById($id);
        foreach (['image', 'banner'] as $field) {
            if ($event->{$field}) {
                ImageHelper::delete($event->{$field});
            }
        }
        $this->eventRepository->delete($event);

        $this->userService->logActivity(auth()->user(), 'delete_event', ['event_id' => $id, 'title' => $event->title]);

        return redirect()->route('admin.events.index')->with('success', 'Event berhasil dihapus');
    }

    public function publish(int $id)
    {
        $event = $this->eventRepository->findById($id);
        
        try {
            $this->eventService->publishEvent($event);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $this->userService->logActivity(auth()->user(), 'publish_event', ['event_id' => $event->id, 'title' => $event->title]);

        return redirect()->back()->with('success', 'Event berhasil dipublikasi');
    }

    public function cancel(int $id)
    {
        $event = $this->eventRepository->findById($id);

        try {
            $this->eventService->cancelEvent($event);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $this->userService->logActivity(auth()->user(), 'cancel_event', ['event_id' => $event->id, 'title' => $event->title]);

        return redirect()->back()->with('success', 'Event berhasil dibatalkan');
    }
}
