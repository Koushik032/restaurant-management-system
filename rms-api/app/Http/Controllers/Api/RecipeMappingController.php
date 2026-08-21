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
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

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
    | Target:
    |     menu_item
    |     add_on
    |
    | Menu item variant recipes are separated by:
    |
    |     target_type
    |     target_id
    |     variant_id
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

                        'variant_id' =>
                            $recipe[
                                'variant_id'
                            ] !== null
                                ? (int) $recipe[
                                    'variant_id'
                                ]
                                : null,

                        'variant_name' =>
                            $recipe[
                                'variant_name'
                            ] ?? null,

                        'image_url' =>
                            $recipe[
                                'image_url'
                            ] ?? null,

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


        $variantId =
            $request->query(
                'variant_id'
            );


        $variantId =
            $variantId !== null
            &&
            $variantId !== ''

                ? (int)
                    $variantId

                : null;


        $menuItem =
            $this->recipeMappingService
                ->getRecipe(
                    $menuItem,
                    $variantId
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
                    $menuItem,
                    $variantId
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Legacy: Save Menu Item Recipe
    |--------------------------------------------------------------------------
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


        $this->ensureManageAccess(
            $request
        );


        /*
        |----------------------------------------------------------------------
        | Route / Body Target Consistency
        |----------------------------------------------------------------------
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


        $variantId =
            $validated[
                'variant_id'
            ]
            ??
            null;


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
                        $request->user(),

                    variantId:
                        $variantId !== null
                            ? (int)
                                $variantId
                            : null,

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

                    $menuItem,

                    $variantId !== null
                        ? (int)
                            $variantId
                        : null

                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Unified: Show Recipe Target
    |--------------------------------------------------------------------------
    |
    | GET /inventory/recipe-mappings/menu_item/{id}
    | GET /inventory/recipe-mappings/add_on/{id}
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


        $variantId =
            $request->query(
                'variant_id'
            );


        $variantId =
            $variantId !== null
            &&
            $variantId !== ''

                ? (int)
                    $variantId

                : null;


        $target =
            $this->recipeMappingService
                ->getTargetRecipe(

                    $targetType,

                    $targetId,

                    $variantId

                );


        $this->loadTargetResourceRelations(
            $target
        );


        $canonicalTargetType =
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

                    $canonicalTargetType,

                    $target,

                    $variantId

                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Unified: Save / Replace Recipe Target
    |--------------------------------------------------------------------------
    |
    | PUT /inventory/recipe-mappings
    |
    */

    public function saveTarget(
        SaveRecipeMappingRequest $request
    ): JsonResponse {

        $validated =
            $request->validated();


        $this->ensureManageAccess(
            $request
        );


        $variantId =
            $validated[
                'variant_id'
            ]
            ??
            null;


        $target =
            $this->recipeMappingService
                ->replaceTargetRecipe(

                    targetType:
                        $validated[
                            'target_type'
                        ],

                    targetId:
                        (int)
                        $validated[
                            'target_id'
                        ],

                    ingredients:
                        $validated[
                            'ingredients'
                        ],

                    user:
                        $request->user(),

                    variantId:
                        $variantId !== null
                            ? (int)
                                $variantId
                            : null

                );


        $this->loadTargetResourceRelations(
            $target
        );


        $canonicalTargetType =
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

                    $canonicalTargetType,

                    $target,

                    $variantId !== null
                        ? (int)
                            $variantId
                        : null

                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Unified: Delete Recipe Target
    |--------------------------------------------------------------------------
    |
    | Deletes recipe rows only.
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


        $variantId =
            $request->query(
                'variant_id'
            );


        $variantId =
            $variantId !== null
            &&
            $variantId !== ''

                ? (int)
                    $variantId

                : null;


        $target =
            $this->recipeMappingService
                ->getTargetRecipe(

                    $targetType,

                    $targetId,

                    $variantId

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

                (int)
                    $target->id,

                $variantId

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
                    (int)
                        $target->id,


                'target_name' =>
                    $targetName,


                'variant_id' =>
                    $canonicalTargetType ===
                    RecipeMappingService::TARGET_MENU_ITEM

                        ? $variantId

                        : null,

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
    MenuItem|AddOn $target,
    ?int $variantId = null
): array {

    $isAddOn =
        $targetType ===
        RecipeMappingService::TARGET_ADD_ON;


    $variant = null;


    /*
    |--------------------------------------------------------------------------
    | Variant Only Exists For Menu Item
    |--------------------------------------------------------------------------
    */

    if (
        !$isAddOn
        &&
        $variantId !== null
        &&
        $target instanceof MenuItem
    ) {

        $variant =
            $target
                ->variants
                ->firstWhere(
                    'id',
                    $variantId
                );
    }


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


        /*
        |--------------------------------------------------------------------------
        | Variant
        |--------------------------------------------------------------------------
        */

        'variant_id' =>
            $isAddOn
                ? null
                : $variantId,


        'variant_name' =>
            $variant?->variant_name,


        /*
        |--------------------------------------------------------------------------
        | Ingredients
        |--------------------------------------------------------------------------
        */

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

    /*
    |--------------------------------------------------------------------------
    | Common Recipe Mapping Relations
    |--------------------------------------------------------------------------
    */

    $target->loadMissing([
        'recipeMappings.menuItem',
        'recipeMappings.variant',
        'recipeMappings.addOn',
        'recipeMappings.rawMaterial.restaurantStock.rawMaterial',
        'recipeMappings.creator',
        'recipeMappings.updater',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Menu Item Only
    |--------------------------------------------------------------------------
    |
    | Only Menu Items support variants.
    |
    */

    if (
        $target instanceof MenuItem
    ) {

        $target->loadMissing([
            'variants',
        ]);
    }
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

            'variant',

            'addOn',

            'rawMaterial.restaurantStock',

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


/*
|--------------------------------------------------------------------------
| Small Internal String Helper
|--------------------------------------------------------------------------
|
| Keeps controller normalization dependency-free.
|
*/

final class StringHelper
{
    public static function normalizeTargetType(
        mixed $value
    ): string {

        $value =
            strtolower(
                trim(
                    (string)
                        $value
                )
            );


        $value =
            str_replace(
                '-',
                '_',
                $value
            );


        return match (
            $value
        ) {

            'menu_item',
            'menuitem' =>
                'menu_item',

            'add_on',
            'addon' =>
                'add_on',

            default =>
                $value,

        };
    }
}