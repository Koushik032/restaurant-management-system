<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard overview retrieved successfully.',
            'data' => [
                'welcome_message' => 'Welcome to the Restaurant Management System.',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role?->display_name,
                ],
                'statistics' => [
                    'total_sales' => 0,
                    'completed_orders' => 0,
                    'active_orders' => 0,
                    'pending_kitchen_orders' => 0,
                    'occupied_tables' => 0,
                    'available_tables' => 0,
                    'low_stock_items' => 0,
                    'upcoming_reservations' => 0,
                ],
            ],
            'errors' => null,
        ]);
    }

    public function adminOverview(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Admin overview retrieved successfully.',
            'data' => [
                'current_user' => $request->user()->name,
                'total_users' => User::query()->count(),
                'total_roles' => Role::query()->count(),
                'total_permissions' => Permission::query()->count(),
            ],
            'errors' => null,
        ]);
    }

    public function managerOverview(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Manager operational overview retrieved successfully.',
            'data' => [
                'current_user' => $request->user()->name,
                'active_orders' => 0,
                'occupied_tables' => 0,
                'pending_payments' => 0,
                'pending_kitchen_orders' => 0,
            ],
            'errors' => null,
        ]);
    }

    public function kitchenOverview(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Kitchen overview retrieved successfully.',
            'data' => [
                'current_user' => $request->user()->name,
                'new_orders' => 0,
                'preparing_orders' => 0,
                'ready_orders' => 0,
                'delayed_orders' => 0,
            ],
            'errors' => null,
        ]);
    }
}