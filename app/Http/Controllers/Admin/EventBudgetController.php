<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventBudgetRequest;
use App\Models\Activity;
use App\Models\Event;
use App\Repositories\EventBudgetRepository;
use App\Services\FileService;
use App\Services\UserService;

class EventBudgetController extends Controller
{
    public function __construct(
        private EventBudgetRepository $eventBudgetRepository,
        private UserService $userService,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $eventBudgets = $this->eventBudgetRepository->all();

        return view('event-budgets.index', compact('eventBudgets'));
    }

    public function create()
    {
        $events = Event::pluck('title', 'id');
        $activities = Activity::pluck('name', 'id');

        return view('event-budgets.create', compact('events', 'activities'));
    }

    public function store(EventBudgetRequest $request)
    {
        $data = $request->validated();

        $eventBudget = $this->eventBudgetRepository->create($data);

        $this->userService->logActivity(auth()->user(), 'create_event_budget', ['event_budget_id' => $eventBudget->id, 'event_id' => $eventBudget->event_id, 'activity_id' => $eventBudget->activity_id]);

        return redirect()->route('admin.event-budgets.index')->with('success', 'Anggaran event berhasil dibuat');
    }

    public function edit(int $id)
    {
        $eventBudget = $this->eventBudgetRepository->findById($id);
        $events = Event::pluck('title', 'id');
        $activities = Activity::pluck('name', 'id');

        return view('event-budgets.edit', compact('eventBudget', 'events', 'activities'));
    }

    public function update(int $id, EventBudgetRequest $request)
    {
        $eventBudget = $this->eventBudgetRepository->findById($id);
        $data = $request->validated();

        $this->eventBudgetRepository->update($eventBudget, $data);

        $this->userService->logActivity(auth()->user(), 'update_event_budget', ['event_budget_id' => $eventBudget->id, 'event_id' => $eventBudget->event_id, 'activity_id' => $eventBudget->activity_id]);

        return redirect()->route('admin.event-budgets.index')->with('success', 'Anggaran event berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $eventBudget = $this->eventBudgetRepository->findById($id);

        $this->eventBudgetRepository->delete($eventBudget);

        $this->userService->logActivity(auth()->user(), 'delete_event_budget', ['event_budget_id' => $id, 'event_id' => $eventBudget->event_id, 'activity_id' => $eventBudget->activity_id]);

        return redirect()->route('admin.event-budgets.index')->with('success', 'Anggaran event berhasil dihapus');
    }
}
