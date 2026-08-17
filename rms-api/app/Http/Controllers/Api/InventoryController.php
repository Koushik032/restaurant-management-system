<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AdjustWarehouseStockRequest;
use App\Http\Requests\Api\StoreRawMaterialRequest;
use App\Http\Requests\Api\UpdateRawMaterialRequest;
use App\Http\Resources\RawMaterialResource;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\WarehouseStockResource;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Summary
    |--------------------------------------------------------------------------
    */

    public function summary(Request $request): JsonResponse
    {
        $this->ensureInventoryViewAccess($request);

        return response()->json([
            'success' => true,
            'message' => 'Inventory summary loaded successfully.',
            'data' => $this->inventoryService->getInventorySummary(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Options
    |--------------------------------------------------------------------------
    */

    public function options(Request $request): JsonResponse
    {
        $this->ensureInventoryViewAccess($request);

        return response()->json([
            'success' => true,
            'message' => 'Inventory options loaded successfully.',
            'data' => $this->inventoryService->getOptions(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Material List
    |--------------------------------------------------------------------------
    */

    public function rawMaterials(Request $request): JsonResponse
    {
        $this->ensureInventoryViewAccess($request);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:180',
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'base_unit' => [
                'nullable',
                'string',
                Rule::in(
                    RawMaterial::allowedUnits()
                ),
            ],

            'stock_status' => [
                'nullable',
                'string',
                Rule::in(
                    WarehouseStock::allowedStatuses()
                ),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'material_name',
                    'category',
                    'base_unit',
                    'created_at',
                    'updated_at',
                ]),
            ],

            'sort_direction' => [
                'nullable',
                'string',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $rawMaterials = $this->inventoryService->getRawMaterials(
            $validated
        );

        /*
        |--------------------------------------------------------------------------
        | Resource Relations
        |--------------------------------------------------------------------------
        |
        | RawMaterialResource exposes both warehouse and restaurant stock
        | snapshots when those relationships are loaded.
        |
        */

        $rawMaterials
            ->getCollection()
            ->loadMissing([
                'restaurantStock.rawMaterial',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Raw materials loaded successfully.',
            'data' => RawMaterialResource::collection(
                $rawMaterials
            ),
            'meta' => $this->paginationMeta(
                $rawMaterials
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Raw Material
    |--------------------------------------------------------------------------
    */

    public function storeRawMaterial(
        StoreRawMaterialRequest $request
    ): JsonResponse {
        $this->ensureInventoryManageAccess($request);

        $rawMaterial = $this->inventoryService->createRawMaterial(
            $request->validated(),
            $request->user()
        );

        $this->loadRawMaterialResourceRelations(
            $rawMaterial
        );

        return response()->json([
            'success' => true,
            'message' => 'Raw material created successfully.',
            'data' => new RawMaterialResource($rawMaterial),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Raw Material
    |--------------------------------------------------------------------------
    */

    public function showRawMaterial(
        Request $request,
        RawMaterial $rawMaterial
    ): JsonResponse {
        $this->ensureInventoryViewAccess($request);

        $this->loadRawMaterialResourceRelations(
            rawMaterial: $rawMaterial,
            withCounts: true
        );

        return response()->json([
            'success' => true,
            'message' => 'Raw material loaded successfully.',
            'data' => new RawMaterialResource($rawMaterial),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Raw Material
    |--------------------------------------------------------------------------
    */

    public function updateRawMaterial(
        UpdateRawMaterialRequest $request,
        RawMaterial $rawMaterial
    ): JsonResponse {
        $this->ensureInventoryManageAccess($request);

        $rawMaterial = $this->inventoryService->updateRawMaterial(
            $rawMaterial,
            $request->validated(),
            $request->user()
        );

        $this->loadRawMaterialResourceRelations(
            $rawMaterial
        );

        return response()->json([
            'success' => true,
            'message' => 'Raw material updated successfully.',
            'data' => new RawMaterialResource($rawMaterial),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Raw Material Status
    |--------------------------------------------------------------------------
    */

    public function toggleRawMaterialStatus(
        Request $request,
        RawMaterial $rawMaterial
    ): JsonResponse {
        $this->ensureInventoryManageAccess($request);

        $rawMaterial = $this->inventoryService
            ->toggleRawMaterialStatus(
                $rawMaterial,
                $request->user()
            );

        $this->loadRawMaterialResourceRelations(
            $rawMaterial
        );

        return response()->json([
            'success' => true,
            'message' => $rawMaterial->is_active
                ? 'Raw material activated successfully.'
                : 'Raw material deactivated successfully.',
            'data' => new RawMaterialResource($rawMaterial),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete / Archive Raw Material
    |--------------------------------------------------------------------------
    */

    public function destroyRawMaterial(
        Request $request,
        RawMaterial $rawMaterial
    ): JsonResponse {
        $this->ensureInventoryManageAccess($request);

        $this->inventoryService->deleteRawMaterial(
            $rawMaterial,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Raw material archived successfully.',
            'data' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Warehouse Stock List
    |--------------------------------------------------------------------------
    */

    public function warehouseStocks(
        Request $request
    ): JsonResponse {
        $this->ensureInventoryViewAccess($request);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:180',
            ],

            'raw_material_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'raw_materials',
                    'id'
                )->whereNull('deleted_at'),
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'base_unit' => [
                'nullable',
                'string',
                Rule::in(
                    RawMaterial::allowedUnits()
                ),
            ],

            'status' => [
                'nullable',
                'string',
                Rule::in(
                    WarehouseStock::allowedStatuses()
                ),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                'string',
                Rule::in([
                    'quantity',
                    'average_unit_cost',
                    'last_received_at',
                    'created_at',
                    'updated_at',
                ]),
            ],

            'sort_direction' => [
                'nullable',
                'string',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $warehouseStocks = $this->inventoryService
            ->getWarehouseStocks($validated);

        return response()->json([
            'success' => true,
            'message' => 'Warehouse stocks loaded successfully.',
            'data' => WarehouseStockResource::collection(
                $warehouseStocks
            ),
            'meta' => $this->paginationMeta(
                $warehouseStocks
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Warehouse Stock
    |--------------------------------------------------------------------------
    */

    public function showWarehouseStock(
        Request $request,
        RawMaterial $rawMaterial
    ): JsonResponse {
        $this->ensureInventoryViewAccess($request);

        $warehouseStock = WarehouseStock::query()
            ->with([
                'rawMaterial',
                'creator',
                'updater',
            ])
            ->where(
                'raw_material_id',
                $rawMaterial->id
            )
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse stock loaded successfully.',
            'data' => new WarehouseStockResource(
                $warehouseStock
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Adjust Warehouse Stock
    |--------------------------------------------------------------------------
    */

    public function adjustWarehouseStock(
        AdjustWarehouseStockRequest $request,
        RawMaterial $rawMaterial
    ): JsonResponse {
        $this->ensureInventoryManageAccess($request);

        $validated = $request->validated();

        $warehouseStock = $this->inventoryService
            ->adjustWarehouseStock(
                $rawMaterial,
                $validated,
                $request->user()
            );

        $message = $validated['adjustment_type']
            === AdjustWarehouseStockRequest::TYPE_INCREASE
                ? 'Warehouse stock increased successfully.'
                : 'Warehouse stock decreased successfully.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new WarehouseStockResource(
                $warehouseStock
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Movement History
    |--------------------------------------------------------------------------
    */

    public function stockMovements(
        Request $request
    ): JsonResponse {
        $this->ensureInventoryViewAccess($request);

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:180',
            ],

            'raw_material_id' => [
                'nullable',
                'integer',

                /*
                |--------------------------------------------------------------------------
                | Historical Filter
                |--------------------------------------------------------------------------
                |
                | Stock movements are immutable history. Archived raw materials
                | must remain filterable here.
                |
                */

                Rule::exists(
                    'raw_materials',
                    'id'
                ),
            ],

            'location' => [
                'nullable',
                'string',
                Rule::in(
                    StockMovement::allowedLocations()
                ),
            ],

            'movement_type' => [
                'nullable',
                'string',
                Rule::in(
                    StockMovement::allowedMovementTypes()
                ),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $stockMovements = $this->inventoryService
            ->getStockMovements($validated);

        return response()->json([
            'success' => true,
            'message' => 'Stock movement history loaded successfully.',
            'data' => StockMovementResource::collection(
                $stockMovements
            ),
            'meta' => $this->paginationMeta(
                $stockMovements
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Load Raw Material Resource Relations
    |--------------------------------------------------------------------------
    */

    private function loadRawMaterialResourceRelations(
        RawMaterial $rawMaterial,
        bool $withCounts = false
    ): RawMaterial {

        $rawMaterial->loadMissing([
            'warehouseStock.rawMaterial',
            'restaurantStock.rawMaterial',
            'creator',
            'updater',
        ]);


        if ($withCounts) {
            $rawMaterial->loadCount([
                'stockMovements',
                'purchaseOrderItems',
            ]);
        }


        return $rawMaterial;
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination Meta
    |--------------------------------------------------------------------------
    */

    private function paginationMeta(
        LengthAwarePaginator $paginator
    ): array {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory View Access
    |--------------------------------------------------------------------------
    */

    private function ensureInventoryViewAccess(
        Request $request
    ): void {
        $this->ensureAuthenticated(
            $request
        );


        $canView =
            $request
                ->user()
                ->hasAnyPermission([
                    'inventory.view',
                    'inventory.manage',
                ]);


        abort_unless(
            $canView,
            403,
            'You do not have permission to view inventory.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Inventory Manage Access
    |--------------------------------------------------------------------------
    */

    private function ensureInventoryManageAccess(
        Request $request
    ): void {
        $this->ensureAuthenticated(
            $request
        );


        $canManage =
            $request
                ->user()
                ->hasPermission(
                    'inventory.manage'
                );


        abort_unless(
            $canManage,
            403,
            'You do not have permission to manage inventory.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    private function ensureAuthenticated(
        Request $request
    ): void {
        abort_unless(
            $request->user(),
            401,
            'Authentication is required.'
        );
    }
}