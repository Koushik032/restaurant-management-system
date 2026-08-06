<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuCategoryRequest;
use App\Http\Requests\UpdateMenuCategoryRequest;
use App\Http\Resources\MenuCategoryResource;
use App\Models\MenuCategory;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MenuCategoryController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of menu categories.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max((int) $request->input('per_page', 10), 1),
            100
        );

        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');

        $query = MenuCategory::query()
            ->withCount('menuItems');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('category_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status === 'available' || $status === '1' || $status === 1) {
            $query->where('is_available', true);
        }

        if ($status === 'unavailable' || $status === '0' || $status === 0) {
            $query->where('is_available', false);
        }

        $categories = $query
            ->orderBy('display_order')
            ->orderBy('category_name')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse(
            MenuCategoryResource::collection($categories),
            $categories,
            'Menu categories retrieved successfully.'
        );
    }

    /**
     * Store a newly created menu category.
     */
    public function store(
        StoreMenuCategoryRequest $request
    ): JsonResponse {
        try {
            $category = DB::transaction(function () use ($request) {
                return MenuCategory::create(
                    $request->validated()
                );
            });

            $category->loadCount('menuItems');

            return $this->createdResponse(
                new MenuCategoryResource($category),
                'Menu category created successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                'Unable to create menu category.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified menu category.
     */
    public function show(
        MenuCategory $menuCategory
    ): JsonResponse {
        $menuCategory->load([
            'menuItems' => function ($query): void {
                $query
                    ->withCount('variants')
                    ->orderBy('menu_name');
            },
        ]);

        $menuCategory->loadCount('menuItems');

        return $this->successResponse(
            new MenuCategoryResource($menuCategory),
            'Menu category retrieved successfully.'
        );
    }

    /**
     * Update the specified menu category.
     */
    public function update(
        UpdateMenuCategoryRequest $request,
        MenuCategory $menuCategory
    ): JsonResponse {
        try {
            DB::transaction(function () use (
                $request,
                $menuCategory
            ): void {
                $menuCategory->update(
                    $request->validated()
                );
            });

            $menuCategory->refresh();
            $menuCategory->loadCount('menuItems');

            return $this->updatedResponse(
                new MenuCategoryResource($menuCategory),
                'Menu category updated successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                'Unable to update menu category.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove the specified menu category.
     */
    public function destroy(
    MenuCategory $menuCategory
): JsonResponse {
    try {
        DB::transaction(function () use (
            $menuCategory
        ): void {
            $menuCategory->delete();
        });

        return response()->json([
            'success' => true,
            'message' =>
                'Menu category and all related menu items deleted successfully.',
            'data' => null,
        ]);
    } catch (Throwable $exception) {
        report($exception);

        return response()->json([
            'success' => false,
            'message' =>
                'Unable to delete the menu category.',
            'error' => app()->isLocal()
                ? $exception->getMessage()
                : null,
        ], 500);
    }
}

    /**
     * Toggle category availability.
     */
    public function toggleStatus(
    MenuCategory $menuCategory
): JsonResponse {
    $menuCategory->update([
        'is_available' => !$menuCategory->is_available,
    ]);

    $menuCategory->refresh();

    return response()->json([
        'success' => true,
        'message' => $menuCategory->is_available
            ? 'Menu category is now available.'
            : 'Menu category is now unavailable.',
        'data' => [
            'id' => $menuCategory->id,
            'category_name' =>
                $menuCategory->category_name,
            'is_available' =>
                (bool) $menuCategory->is_available,
        ],
    ]);
}
}