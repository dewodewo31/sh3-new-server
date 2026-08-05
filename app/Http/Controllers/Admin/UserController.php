<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Repositories\UserRepository;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private UserService $userService,
    ) {}

    public function index()
    {
        $users = $this->userRepository->paginate();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(UserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());

        $this->userService->logActivity(auth()->user(), 'create_user', ['user_id' => $user->id, 'email' => $user->email]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dibuat');
    }

    public function show(int $id)
    {
        $user = $this->userRepository->findById($id, ['activityLogs']);

        return view('users.show', compact('user'));
    }

    public function edit(int $id)
    {
        $user = $this->userRepository->findById($id);

        return view('users.edit', compact('user'));
    }

    public function update(int $id, UserRequest $request)
    {
        $user = $this->userRepository->findById($id);
        $this->userService->updateUser($user, $request->validated());

        $this->userService->logActivity(auth()->user(), 'update_user', ['user_id' => $user->id, 'email' => $user->email]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diupdate');
    }

    public function destroy(int $id)
    {
        $user = $this->userRepository->findById($id);
        $this->userRepository->delete($user);

        $this->userService->logActivity(auth()->user(), 'delete_user', ['user_id' => $id, 'email' => $user->email]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus');
    }

    public function toggleActive(int $id)
    {
        $user = $this->userRepository->findById($id);
        $oldStatus = $user->is_active;
        $this->userService->toggleActive($user);

        $newStatus = !$oldStatus;
        $this->userService->logActivity(auth()->user(), 'toggle_user_active', ['user_id' => $user->id, 'was_active' => $oldStatus, 'now_active' => $newStatus]);

        return redirect()->back()->with('success', 'Status user berhasil diubah');
    }
}
