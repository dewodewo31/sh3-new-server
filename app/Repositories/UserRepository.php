<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email)
    {
        return $this->findFirstBy('email', $email);
    }

    public function findByRole(string $role)
    {
        return $this->findBy('role', $role);
    }

    public function findActiveUsers()
    {
        return $this->model->where('is_active', true)->get();
    }

    public function updateLastLogin(User $user): void
    {
        $user->update(['last_login' => now()]);
    }
}
