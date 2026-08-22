<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Repositories\ActivityRepository;
use App\Services\FileService;
use App\Services\UserService;

class ActivityController extends Controller
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private UserService $userService,
        private FileService $fileService,
    ) {}

    public function index()
    {
        $activities = $this->activityRepository->findActive();

        return view('activities.index', compact('activities'));
    }

    public function create()
    {
        return view('activities.create');
    }

    public function store(ActivityRequest $request)
    {
        $data = $request->validated();

        $activity = $this->activityRepository->create($data);

        $this->userService->logActivity(auth()->user(), 'create_activity', ['activity_id' => $activity->id, 'name' => $activity->name]);

        return redirect()->route('admin.activities.index')->with('success', 'Aktivitas berhasil dibuat');
    }

    public function edit(int $id)
    {
        $activity = $this->activityRepository->findById($id);

        return view('activities.edit', compact('activity'));
    }

    public function update(int $id, ActivityRequest $request)
    {
        $activity = $this->activityRepository->findById($id);
        $data = $request->validated();

        $this->activityRepository->update($activity, $data);

        $this->userService->logActivity(auth()->user(), 'update_activity', ['activity_id' => $activity->id, 'name' => $activity->name]);

        return redirect()->route('admin.activities.index')->with('success', 'Aktivitas berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $activity = $this->activityRepository->findById($id);

        $this->activityRepository->delete($activity);

        $this->userService->logActivity(auth()->user(), 'delete_activity', ['activity_id' => $id, 'name' => $activity->name]);

        return redirect()->route('admin.activities.index')->with('success', 'Aktivitas berhasil dihapus');
    }
}
