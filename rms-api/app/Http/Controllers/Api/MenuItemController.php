<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MenuItemController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of menu items.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max(
                (int) $request->input('per_page', 10),
                1
            ),
            100
        );

        $search = trim(
            (string) $request->input('search', '')
        );

        $categoryId = $request->input(
            'menu_category_id'
        );

        $itemType = $request->input(
            'item_type'
        );

        $status = $request->input(
            'status'
        );

        $featured = $request->input(
            'featured'
        );

        $query = MenuItem::query()
            ->with('category')
            ->withCount('variants');

        /*
        |--------------------------------------------------------------------------
        | Search filter
        |--------------------------------------------------------------------------
        */

        if ($search !== '') {
            $query->search($search);
        }

        /*
        |--------------------------------------------------------------------------
        | Category filter
        |--------------------------------------------------------------------------
        */

        if (
            $categoryId !== null &&
            $categoryId !== ''
        ) {
            $query->where(
                'menu_category_id',
                (int) $categoryId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Item type filter
        |--------------------------------------------------------------------------
        */

        if (
            $itemType !== null &&
            $itemType !== '' &&
            in_array(
                $itemType,
                MenuItem::allowedTypes(),
                true
            )
        ) {
            $query->where(
                'item_type',
                $itemType
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Availability filter
        |--------------------------------------------------------------------------
        */

        if (
            $status === 'available' ||
            $status === '1' ||
            $status === 1 ||
            $status === true
        ) {
            $query->where(
                'is_available',
                true
            );
        }

        if (
            $status === 'unavailable' ||
            $status === '0' ||
            $status === 0 ||
            $status === false
        ) {
            $query->where(
                'is_available',
                false
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Featured filter
        |--------------------------------------------------------------------------
        */

        if (
            $featured === '1' ||
            $featured === 1 ||
            $featured === true
        ) {
            $query->where(
                'is_featured',
                true
            );
        }

        if (
            $featured === '0' ||
            $featured === 0 ||
            $featured === false
        ) {
            $query->where(
                'is_featured',
                false
            );
        }

        $menuItems = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginatedResponse(
            MenuItemResource::collection(
                $menuItems
            ),
            $menuItems,
            'Menu items retrieved successfully.'
        );
    }

    /**
     * Store a newly created menu item.
     */
    public function store(
        StoreMenuItemRequest $request
    ): JsonResponse {
        $imagePath = null;

        try {
            if ($request->hasFile('image')) {
                $imagePath = $request
                    ->file('image')
                    ->store(
                        'menu-items',
                        'public'
                    );
            }

            $menuItem = DB::transaction(
                function () use (
                    $request,
                    $imagePath
                ): MenuItem {
                    $data = $request->validated();

                    unset($data['image']);

                    $data['image_path'] = $imagePath;

                    return MenuItem::create($data);
                }
            );

            $menuItem->load('category');
            $menuItem->loadCount('variants');

            return $this->createdResponse(
                new MenuItemResource($menuItem),
                'Menu item created successfully.'
            );
        } catch (Throwable $exception) {
            if (
                $imagePath &&
                Storage::disk('public')
                    ->exists($imagePath)
            ) {
                Storage::disk('public')
                    ->delete($imagePath);
            }

            report($exception);

            return $this->errorResponse(
                app()->isLocal()
                    ? $exception->getMessage()
                    : 'Unable to create menu item.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified menu item.
     */
    public function show(
        MenuItem $menuItem
    ): JsonResponse {
        $menuItem->load([
            'category',

            'variants' => function (
                $query
            ): void {
                $query->orderBy('price');
            },
        ]);

        $menuItem->loadCount('variants');

        return $this->successResponse(
            new MenuItemResource($menuItem),
            'Menu item retrieved successfully.'
        );
    }

    /**
     * Update the specified menu item.
     */
    public function update(
        UpdateMenuItemRequest $request,
        MenuItem $menuItem
    ): JsonResponse {
        $oldImagePath = $menuItem->image_path;
        $newImagePath = null;

        try {
            /*
             * Upload the new image before starting
             * the database transaction.
             */
            if ($request->hasFile('image')) {
                $newImagePath = $request
                    ->file('image')
                    ->store(
                        'menu-items',
                        'public'
                    );
            }

            DB::transaction(
                function () use (
                    $request,
                    $menuItem,
                    $newImagePath
                ): void {
                    $data = $request->validated();

                    /*
                     * The uploaded image object itself
                     * must not be inserted into the table.
                     */
                    unset($data['image']);

                    $removeImage = (bool) (
                        $data['remove_image'] ?? false
                    );

                    unset($data['remove_image']);

                    /*
                     * Replace the old image with the
                     * newly uploaded image.
                     */
                    if ($newImagePath !== null) {
                        $data['image_path'] =
                            $newImagePath;
                    } elseif ($removeImage) {
                        /*
                         * Remove the current image without
                         * uploading a replacement.
                         */
                        $data['image_path'] = null;
                    }

                    $menuItem->update($data);
                }
            );

            /*
             * Delete the previous image only after the
             * database update has completed successfully.
             */
            $shouldDeleteOldImage =
                $newImagePath !== null ||
                $request->boolean(
                    'remove_image'
                );

            if (
                $shouldDeleteOldImage &&
                $oldImagePath &&
                Storage::disk('public')
                    ->exists($oldImagePath)
            ) {
                Storage::disk('public')
                    ->delete($oldImagePath);
            }

            $menuItem->refresh();
            $menuItem->load('category');
            $menuItem->loadCount('variants');

            return $this->updatedResponse(
                new MenuItemResource($menuItem),
                'Menu item updated successfully.'
            );
        } catch (Throwable $exception) {
            /*
             * If the database update fails, delete only
             * the newly uploaded unused image.
             */
            if (
                $newImagePath &&
                Storage::disk('public')
                    ->exists($newImagePath)
            ) {
                Storage::disk('public')
                    ->delete($newImagePath);
            }

            report($exception);

            return $this->errorResponse(
                app()->isLocal()
                    ? $exception->getMessage()
                    : 'Unable to update menu item.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Soft delete the specified menu item.
     */
    public function destroy(
        MenuItem $menuItem
    ): JsonResponse {
        try {
            DB::transaction(
                function () use (
                    $menuItem
                ): void {
                    /*
                     * Delete the variants related to
                     * this menu item.
                     */
                    $menuItem
                        ->variants()
                        ->delete();

                    /*
                     * MenuItem uses SoftDeletes, so the
                     * menu item itself is soft deleted.
                     */
                    $menuItem->delete();
                }
            );

            return $this->deletedResponse(
                'Menu item deleted successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                app()->isLocal()
                    ? $exception->getMessage()
                    : 'Unable to delete menu item.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Toggle menu item availability.
     */
    public function toggleStatus(
        MenuItem $menuItem
    ): JsonResponse {
        try {
            DB::transaction(
                function () use (
                    $menuItem
                ): void {
                    $currentStatus =
                        (bool) $menuItem
                            ->is_available;

                    $menuItem->update([
                        'is_available' =>
                            !$currentStatus,
                    ]);
                }
            );

            $menuItem->refresh();
            $menuItem->load('category');
            $menuItem->loadCount('variants');

            return $this->updatedResponse(
                new MenuItemResource($menuItem),
                $menuItem->is_available
                    ? 'Menu item is now available.'
                    : 'Menu item is now unavailable.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                app()->isLocal()
                    ? $exception->getMessage()
                    : 'Unable to update menu item availability.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(
        MenuItem $menuItem
    ): JsonResponse {
        try {
            DB::transaction(
                function () use (
                    $menuItem
                ): void {
                    $currentStatus =
                        (bool) $menuItem
                            ->is_featured;

                    $menuItem->update([
                        'is_featured' =>
                            !$currentStatus,
                    ]);
                }
            );

            $menuItem->refresh();
            $menuItem->load('category');
            $menuItem->loadCount('variants');

            return $this->updatedResponse(
                new MenuItemResource($menuItem),
                $menuItem->is_featured
                    ? 'Menu item has been marked as featured.'
                    : 'Menu item has been removed from featured items.'
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->errorResponse(
                app()->isLocal()
                    ? $exception->getMessage()
                    : 'Unable to update featured status.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}