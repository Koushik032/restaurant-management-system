<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private const MAX_FAILED_LOGIN_ATTEMPTS = 5;

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $login = $validated['login'];

        $user = User::query()
            ->with([
                'role.permissions',
            ])
            ->where(function ($query) use ($login): void {
                $query
                    ->where('email', $login)
                    ->orWhere('username', $login);
            })
            ->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'The provided login credentials are incorrect.',
                'errors' => [
                    'login' => [
                        'The provided login credentials are incorrect.',
                    ],
                ],
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact the administrator.',
                'errors' => null,
            ], 403);
        }

        if ($user->blocked_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is blocked. Please contact the administrator.',
                'errors' => null,
            ], 403);
        }

        if (! $user->role || ! $user->role->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your assigned role is unavailable.',
                'errors' => null,
            ], 403);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            $this->recordFailedLogin($user);

            $remainingAttempts = max(
                0,
                self::MAX_FAILED_LOGIN_ATTEMPTS
                - $user->fresh()->failed_login_attempts
            );

            return response()->json([
                'success' => false,
                'message' => $remainingAttempts > 0
                    ? 'The provided login credentials are incorrect.'
                    : 'Your account has been blocked after too many failed login attempts.',
                'data' => [
                    'remaining_attempts' => $remainingAttempts,
                ],
                'errors' => [
                    'login' => [
                        'The provided login credentials are incorrect.',
                    ],
                ],
            ], $remainingAttempts > 0 ? 401 : 403);
        }

        return DB::transaction(function () use ($user, $validated): JsonResponse {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'last_login_at' => now(),
            ])->save();

            /*
             * Delete old token with the same device name.
             * This prevents duplicate tokens for the same browser/device.
             */
            $deviceName = $validated['device_name'] ?? 'RMS Client';

            $user->tokens()
                ->where('name', $deviceName)
                ->delete();

            $plainTextToken = $user
                ->createToken($deviceName)
                ->plainTextToken;

            $user->load([
                'role.permissions',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'token_type' => 'Bearer',
                    'access_token' => $plainTextToken,
                    'user' => new UserResource($user),
                ],
                'errors' => null,
            ]);
        });
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load([
            'role.permissions',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
            'errors' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill([
                'last_logout_at' => now(),
            ])->save();

            /*
             * Only delete the token used for the current request.
             * Other devices remain logged in.
             */
            $request->user()
                ->currentAccessToken()
                ?->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => null,
            'errors' => null,
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'last_logout_at' => now(),
            ])->save();

            $user->tokens()->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'You have been logged out from all devices.',
            'data' => null,
            'errors' => null,
        ]);
    }

    private function recordFailedLogin(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->increment('failed_login_attempts');

            $user->refresh();

            if (
                $user->failed_login_attempts
                >= self::MAX_FAILED_LOGIN_ATTEMPTS
            ) {
                $user->forceFill([
                    'blocked_at' => now(),
                ])->save();

                $user->tokens()->delete();
            }
        });
    }
}