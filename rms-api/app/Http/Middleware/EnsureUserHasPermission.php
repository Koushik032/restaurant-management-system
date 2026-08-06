<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions
    ): Response {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        if ($user->blocked_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is blocked.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $user->loadMissing('role.permissions');

        if (! $user->role || ! $user->role->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your assigned role is unavailable.',
                'data' => null,
                'errors' => null,
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        /*
         * Multiple permissions দিলে user-এর যেকোনো একটি permission
         * থাকলেই access দেওয়া হবে।
         */
        $hasPermission = collect($permissions)
            ->contains(
                fn (string $permission): bool =>
                    $user->hasPermission($permission)
            );

        if (! $hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'data' => [
                    'required_permissions' => $permissions,
                    'user_role' => $user->role->name,
                ],
                'errors' => null,
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}