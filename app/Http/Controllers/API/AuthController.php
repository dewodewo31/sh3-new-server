<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private UserService $userService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login($request->validated());

        $token = $this->authService->generateToken($user);

        $this->userService->logActivity($user, 'login');

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->register($data);

        $token = $this->authService->generateToken($user);

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => $this->userPayload($user->load('participants')),
            'token' => $token,
        ], 201);
    }

    public function logout(): JsonResponse
    {
        $user = auth()->user();

        $this->userService->logActivity($user, 'logout');
        $this->authService->revokeToken($user);

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload(auth()->user()->load('participants')),
        ]);
    }

    public function refresh(): JsonResponse
    {
        $user = auth()->user();

        $token = $this->authService->refreshToken($user);

        $this->userService->logActivity($user, 'refresh');

        return response()->json([
            'message' => 'Token berhasil diperbarui.',
            'token' => $token,
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendResetLink($request->validated());

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Link reset password telah dikirim ke email Anda.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Password berhasil direset.',
        ]);
    }

    private function userPayload($user): array
    {
        $user->setAttribute('avatar', ImageHelper::getUrl($user->avatar));

        return $user->toArray();
    }

}
