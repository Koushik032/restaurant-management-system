<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemVariantRequest;
use App\Http\Requests\UpdateMenuItemVariantRequest;
use App\Http\Resources\MenuItemVariantResource;
use App\Models\MenuItemVariant;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MenuItemVariantController extends Controller
{
    use ApiResponse;

    /**
     * Display a paginated listing of menu item variants.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max((int) $request->input('per_page', 10), 1),
            100
        );

        $search = trim((string) $request->input('search', ''));
        $menuItemId = $request->input('menu_item_id');
        $status = $request->input('status');

        $query = MenuItemVariant::query()
            ->with('menuItem');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where(
                        'variant_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'menuItem',
                        function (Builder $itemQuery) use ($search): void {
                            $itemQuery->where(
                                'menu_name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        if ($menuItemId !== null && $menuItemId !== '') {
            $query->where(
                'menu_item_id',
                (int) $menuItemId
            );
        }

        if (
            $status === 'available' ||
            $status === '1' ||
            $status === 1
        ) {
            $query->where('is_available', true);
        }

        if (
            $status === 'unavailable' ||
            $status === '0' ||
            $status === 0
        ) {
            $query->where('is_available', false);
        }

        $variants = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse(
            MenuItemVariantResource::collection($variants),
            $variants,
            'Menu item variants retrieved successfully.'
        );
    }

    /**
     * Store a newly created variant.
     */
    public function store(
        StoreMenuItemVariantRequest $request
    ): JsonResponse {
        try {
            $variant = DB::transaction(function () use ($request) {
                return MenuItemVariant::create(
                    $request->validated()
                );
            });

            $variant->load('menuItem');

            return $this->createdResponse(
                new MenuItemVariantResource($variant),
                'Menu item variant created successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                'Unable to create menu item variant.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified variant.
     */
    public function show(
        MenuItemVariant $menuVariant
    ): JsonResponse {
        $menuVariant->load('menuItem');

        return $this->successResponse(
            new MenuItemVariantResource($menuVariant),
            'Menu item variant retrieved successfully.'
        );
    }

    /**
     * Update the specified variant.
     */
    public function update(
        UpdateMenuItemVariantRequest $request,
        MenuItemVariant $menuVariant
    ): JsonResponse {
        try {
            DB::transaction(function () use (
                $request,
                $menuVariant
            ): void {
                $menuVariant->update(
                    $request->validated()
                );
            });

            $menuVariant->refresh();
            $menuVariant->load('menuItem');

            return $this->updatedResponse(
                new MenuItemVariantResource($menuVariant),
                'Menu item variant updated successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                'Unable to update menu item variant.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove the specified variant.
     */
    public function destroy(
        MenuItemVariant $menuVariant
    ): JsonResponse {
        try {
            DB::transaction(function () use ($menuVariant): void {
                $menuVariant->delete();
            });

            return $this->deletedResponse(
                'Menu item variant deleted successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                'Unable to delete menu item variant.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Toggle variant availability.
     */
    public function toggleStatus(
        MenuItemVariant $menuVariant
    ): JsonResponse {
        try {
            $menuVariant->update([
                'is_available' => !$menuVariant->is_available,
            ]);

            $menuVariant->refresh();
            $menuVariant->load('menuItem');

            return $this->updatedResponse(
                new MenuItemVariantResource($menuVariant),
                $menuVariant->is_available
                    ? 'Menu item variant is now available.'
                    : 'Menu item variant is now unavailable.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                'Unable to update variant availability.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}