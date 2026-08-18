<?php

namespace App\Services;

use App\Helpers\ImageHelper;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        if (! empty($data['avatar']) && is_object($data['avatar'])) {
            $data['avatar'] = ImageHelper::upload($data['avatar'], 'users');
        }

        return $this->userRepository->create($data);
    }

    public function updateUser(User $user, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        if (! empty($data['avatar']) && is_object($data['avatar'])) {
            if ($user->avatar) {
                ImageHelper::delete($user->avatar);
            }
            $data['avatar'] = ImageHelper::upload($data['avatar'], 'users');
        }

        return $this->userRepository->update($user, $data);
    }

    public function toggleActive(User $user): void
    {
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function logActivity(User $user, string $action, array $details = []): void
    {
        $user->activityLogs()->create([
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
