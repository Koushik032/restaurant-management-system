<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveRecipeMappingRequest;
use App\Http\Resources\RecipeMappingResource;
use App\Models\AddOn;
use App\Models\MenuItem;
use App\Models\RecipeMapping;
use App\Services\RecipeMappingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;


class RecipeMappingController extends Controller
{
    public function __construct(
        private readonly RecipeMappingService
            $recipeMappingService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Recipe Mapping List
    |--------------------------------------------------------------------------
    |
    | Returns one row per recipe target.
    |
    | Target may be:
    |     menu_item
    |     add_on
    |
    */

    public function index(
        Request $request
    ): JsonResponse {
        $this->ensureViewAccess(
            $request
        );


        $recipes =
            $this->recipeMappingService
                ->getRecipeMappingList();


        $data =
            $recipes
                ->map(
                    function (
                        array $recipe
                    ): array {
                        /** @var Collection<int, RecipeMapping> $ingredients */
                        $ingredients =
                            $recipe[
                                'ingredients'
                            ];


                        foreach (
                            $ingredients
                            as
                            $mapping
                        ) {
                            $this->loadMappingResourceRelations(
                                $mapping
                            );
                        }


                        return [
                            'target_type' =>
                                $recipe[
                                    'target_type'
                                ],

                            'target_id' =>
                                (int) $recipe[
                                    'target_id'
                                ],

                            'target_name' =>
                                $recipe[
                                    'target_name'
                                ],

                            'is_available' =>
                                (bool) $recipe[
                                    'is_available'
                                ],

                            'ingredient_count' =>
                                $ingredients
                                    ->count(),

                            'ingredients' =>
                                RecipeMappingResource::collection(
                                    $ingredients
                                ),
                        ];
                    }
                )
                ->values();


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Recipe mappings loaded successfully.',

            'data' =>
                $data,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Legacy: Show Menu Item Recipe
    |--------------------------------------------------------------------------
    |
    | Preserved for the existing route:
    |
    | GET /inventory/recipe-mappings/{menuItem}
    |
    */

    public function show(
        Request $request,
        MenuItem $menuItem
    ): JsonResponse {
        $this->ensureViewAccess(
            $request
        );


        $menuItem =
            $this->recipeMappingService
                ->getRecipe(
                    $menuItem
                );


        $this->loadTargetResourceRelations(
            $menuItem
        );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Recipe mapping loaded successfully.',

            'data' =>
                $this->targetPayload(
                    RecipeMappingService::TARGET_MENU_ITEM,
                    $menuItem
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Legacy: Save / Replace Menu Item Recipe
    |--------------------------------------------------------------------------
    |
    | Preserved for the existing route:
    |
    | PUT /inventory/recipe-mappings/{menuItem}
    |
    */

    public function update(
        SaveRecipeMappingRequest $request,
        MenuItem $menuItem
    ): JsonResponse {
        $validated =
            $request->validated();


        /*
        |--------------------------------------------------------------------------
        | Route / Body Target Consistency
        |--------------------------------------------------------------------------
        */

        if (
            $validated[
                'target_type'
            ]
            !==
            RecipeMappingService::TARGET_MENU_ITEM
            ||
            (int) $validated[
                'target_id'
            ]
            !==
            (int) $menuItem->id
        ) {
            throw ValidationException::withMessages([
                'target_id' => [
                    'Recipe target does not match the selected menu item.',
                ],
            ]);
        }


        $menuItem =
            $this->recipeMappingService
                ->replaceRecipe(
                    menuItem:
                        $menuItem,

                    ingredients:
                        $validated[
                            'ingredients'
                        ],

                    user:
                        $request->user()
                );


        $this->loadTargetResourceRelations(
            $menuItem
        );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Recipe mapping saved successfully.',

            'data' =>
                $this->targetPayload(
                    RecipeMappingService::TARGET_MENU_ITEM,
                    $menuItem
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Unified: Show Recipe Target
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | GET /inventory/recipe-mappings/menu_item/5
    | GET /inventory/recipe-mappings/add_on/3
    |
    */

    public function showTarget(
        Request $request,
        string $targetType,
        int $targetId
    ): JsonResponse {
        $this->ensureViewAccess(
            $request
        );


        $target =
            $this->recipeMappingService
                ->getTargetRecipe(
                    $targetType,
                    $targetId
                );


        $this->loadTargetResourceRelations(
            $target
        );


        $targetType =
            $target instanceof AddOn
                ? RecipeMappingService::TARGET_ADD_ON
                : RecipeMappingService::TARGET_MENU_ITEM;


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Recipe mapping loaded successfully.',

            'data' =>
                $this->targetPayload(
                    $targetType,
                    $target
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Unified: Save / Replace Recipe Target
    |--------------------------------------------------------------------------
    */

    public function saveTarget(
        SaveRecipeMappingRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();


        $target =
            $this->recipeMappingService
                ->replaceTargetRecipe(
                    targetType:
                        $validated[
                            'target_type'
                        ],

                    targetId:
                        (int) $validated[
                            'target_id'
                        ],

                    ingredients:
                        $validated[
                            'ingredients'
                        ],

                    user:
                        $request->user()
                );


        $this->loadTargetResourceRelations(
            $target
        );


        $targetType =
            $target instanceof AddOn
                ? RecipeMappingService::TARGET_ADD_ON
                : RecipeMappingService::TARGET_MENU_ITEM;


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Recipe mapping saved successfully.',

            'data' =>
                $this->targetPayload(
                    $targetType,
                    $target
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Unified: Delete Recipe Target
    |--------------------------------------------------------------------------
    |
    | Deletes recipe definition rows only.
    |
    */

    public function destroyTarget(
        Request $request,
        string $targetType,
        int $targetId
    ): JsonResponse {
        $this->ensureManageAccess(
            $request
        );


        $target =
            $this->recipeMappingService
                ->getTargetRecipe(
                    $targetType,
                    $targetId
                );


        $canonicalTargetType =
            $target instanceof AddOn
                ? RecipeMappingService::TARGET_ADD_ON
                : RecipeMappingService::TARGET_MENU_ITEM;


        $targetName =
            $target instanceof AddOn
                ? $target->add_on_name
                : $target->menu_name;


        $this->recipeMappingService
            ->deleteTargetRecipe(
                $canonicalTargetType,
                (int) $target->id
            );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Recipe mapping deleted successfully.',

            'data' => [
                'target_type' =>
                    $canonicalTargetType,

                'target_id' =>
                    (int) $target->id,

                'target_name' =>
                    $targetName,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Target Payload
    |--------------------------------------------------------------------------
    */

    private function targetPayload(
        string $targetType,
        MenuItem|AddOn $target
    ): array {
        $isAddOn =
            $targetType
            ===
            RecipeMappingService::TARGET_ADD_ON;


        return [
            'target_type' =>
                $targetType,

            'target_id' =>
                (int) $target->id,

            'target_name' =>
                $isAddOn
                    ? $target->add_on_name
                    : $target->menu_name,

            'menu_item_id' =>
                $isAddOn
                    ? null
                    : (int) $target->id,

            'add_on_id' =>
                $isAddOn
                    ? (int) $target->id
                    : null,

            'is_available' =>
                (bool) $target->is_available,

            'ingredient_count' =>
                $target
                    ->recipeMappings
                    ->count(),

            'ingredients' =>
                RecipeMappingResource::collection(
                    $target
                        ->recipeMappings
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Target Resource Relations
    |--------------------------------------------------------------------------
    */

    private function loadTargetResourceRelations(
        MenuItem|AddOn $target
    ): void {
        $target->loadMissing([
            'recipeMappings.menuItem',
            'recipeMappings.addOn',
            'recipeMappings.rawMaterial.restaurantStock.rawMaterial',
            'recipeMappings.creator',
            'recipeMappings.updater',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Single Mapping Resource Relations
    |--------------------------------------------------------------------------
    */

    private function loadMappingResourceRelations(
        RecipeMapping $mapping
    ): void {
        $mapping->loadMissing([
            'menuItem',
            'addOn',
            'rawMaterial.restaurantStock.rawMaterial',
            'creator',
            'updater',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | View Access
    |--------------------------------------------------------------------------
    */

    private function ensureViewAccess(
        Request $request
    ): void {
        abort_unless(
            $request->user(),
            401,
            'Authentication is required.'
        );


        abort_unless(
            $request
                ->user()
                ->hasAnyPermission([
                    'inventory.view',
                    'inventory.manage',
                ]),

            403,

            'You do not have permission to view recipe mappings.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Manage Access
    |--------------------------------------------------------------------------
    */

    private function ensureManageAccess(
        Request $request
    ): void {
        abort_unless(
            $request->user(),
            401,
            'Authentication is required.'
        );


        abort_unless(
            $request
                ->user()
                ->hasPermission(
                    'inventory.manage'
                ),

            403,

            'You do not have permission to manage recipe mappings.'
        );
    }
}