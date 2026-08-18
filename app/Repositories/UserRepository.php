<?php

namespace App\Repositories;

use App\Models\User;
use App\Support\Sort;

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

    public function findByUsername(string $username)
    {
        return $this->findFirstBy('username', $username);
    }

    public function findByRole(string $role)
    {
        return $this->findBy('role', $role);
    }

    public function findActiveUsers()
    {
        return $this->model->where('is_active', true)->get();
    }

    public function paginateSortedNonParticipant(array $allowed, int $perPage = 15, array $relations = [], string $default = 'created_at', string $defaultDirection = 'desc')
    {
        return Sort::apply(
            $this->model->whereNotIn('role', ['participant'])->with($relations),
            $allowed,
            $default,
            $defaultDirection,
        )->paginate($perPage)->withQueryString();
    }

    public function updateLastLogin(User $user): void
    {
        $user->update(['last_login' => now()]);
    }
}
