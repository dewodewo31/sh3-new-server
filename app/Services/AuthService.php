<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function login(array $credentials): User
    {
        $user = $this->userRepository->findByUsername($credentials['username']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Akun Anda telah dinonaktifkan.'],
            ]);
        }

        if ($user->role !== 'participant') {
            throw ValidationException::withMessages([
                'username' => ['Akun ini bukan peserta.'],
            ]);
        }

        $this->userRepository->updateLastLogin($user);

        return $user;
    }

    public function generateToken(User $user, string $name = 'api-token'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function revokeToken(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    public function refreshToken(User $user): string
    {
        $user->currentAccessToken()->delete();

        return $this->generateToken($user);
    }

    public function sendResetLink(array $credentials): string
    {
        return Password::sendResetLink($credentials);
    }

    public function resetPassword(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function (User $user, string $password) {
                $user->update(['password' => $password]);
                $user->tokens()->delete();
            }
        );
    }

    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $username = $data['username'] ?? $this->generateUsername($data['name']);

            $user = User::create([
                'name' => $data['name'],
                'username' => $username,
                'email' => $data['email'],
                'password' => $data['password'] ?? Str::random(60),
                'role' => 'participant',
                'is_active' => true,
            ]);

            Participant::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'address' => $data['address'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_phone' => $data['emergency_phone'] ?? null,
                'medical_conditions' => $data['medical_conditions'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'jersey_size' => $data['jersey_size'] ?? null,
            ]);

            return $user;
        });
    }

    private function generateUsername(string $name): string
    {
        $base = Str::slug($name, '_');
        $base = str_replace('-', '_', $base);
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);
        $base = Str::lower($base);
        $base = substr($base, 0, 30);

        $username = $base;
        $suffix = 1;
        while (User::where('username', $username)->exists()) {
            $suffixPart = (string) $suffix;
            $maxLen = 30 - strlen($suffixPart) - 1;
            $truncated = $maxLen > 0 ? substr($base, 0, $maxLen) : substr($base, 0, 25);
            $username = $truncated.'_'.$suffixPart;
            $suffix++;
        }

        return $username;
    }
}
