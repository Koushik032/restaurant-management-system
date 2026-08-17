<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReceivePurchaseOrderRequest;
use App\Http\Requests\Api\StorePurchaseOrderRequest;
use App\Http\Requests\Api\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderReceiptResource;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseReceiveService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class PurchaseOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        private readonly PurchaseOrderService
            $purchaseOrderService,

        private readonly PurchaseReceiveService
            $purchaseReceiveService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Order List
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $orders =
            $this->purchaseOrderService
                ->getPurchaseOrders(
                    $request->all()
                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Purchase orders loaded successfully.',

            'data' =>
                PurchaseOrderResource::collection(
                    $orders
                ),

            'meta' =>
                $this->paginationMeta(
                    $orders
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Store Purchase Order
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePurchaseOrderRequest $request
    ): JsonResponse {

        $purchaseOrder =
            $this->purchaseOrderService
                ->createPurchaseOrder(

                    data:
                        $request->validated(),

                    user:
                        $request->user()

                );


        $this->loadPurchaseOrderRelations(
            $purchaseOrder
        );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Purchase order created successfully.',

            'data' =>
                new PurchaseOrderResource(
                    $purchaseOrder
                ),

        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Purchase Order
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $this->loadPurchaseOrderRelations(
            $purchaseOrder
        );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Purchase order loaded successfully.',

            'data' =>
                new PurchaseOrderResource(
                    $purchaseOrder
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdatePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        $purchaseOrder =
            $this->purchaseOrderService
                ->updatePurchaseOrder(

                    purchaseOrder:
                        $purchaseOrder,

                    data:
                        $request->validated(),

                    user:
                        $request->user()

                );


        $this->loadPurchaseOrderRelations(
            $purchaseOrder
        );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Purchase order updated successfully.',

            'data' =>
                new PurchaseOrderResource(
                    $purchaseOrder
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        Request $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        $this->ensureManageAccess(
            $request
        );


        $allowedStatuses = [

            PurchaseOrder::STATUS_ORDERED,

            PurchaseOrder::STATUS_CANCELLED,

        ];


        /*
        |--------------------------------------------------------------------------
        | Normalize Input
        |--------------------------------------------------------------------------
        */

        $inputStatus =
            strtolower(
                trim(
                    (string) $request
                        ->input(
                            'status'
                        )
                )
            );


        foreach (
            $allowedStatuses
            as
            $allowedStatus
        ) {

            if (
                strcasecmp(
                    $inputStatus,
                    $allowedStatus
                ) === 0
            ) {

                $request->merge([
                    'status' =>
                        $allowedStatus,
                ]);


                break;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate(

                [

                    'status' => [
                        'required',
                        'string',

                        Rule::in(
                            $allowedStatuses
                        ),
                    ],

                ],

                [

                    'status.required' =>
                        'Purchase order status is required.',

                    'status.in' =>
                        'Only Ordered or Cancelled status can be selected manually.',

                ]

            );


        /*
        |--------------------------------------------------------------------------
        | Update Inside Transaction
        |--------------------------------------------------------------------------
        */

        $purchaseOrder =
            DB::transaction(

                function () use (
                    $purchaseOrder,
                    $validated,
                    $request
                ): PurchaseOrder {

                    $lockedPurchaseOrder =
                        PurchaseOrder::query()

                            ->lockForUpdate()

                            ->findOrFail(
                                $purchaseOrder->id
                            );


                    $currentStatus =
                        $this->normalizeStatus(
                            $lockedPurchaseOrder
                                ->status
                        );


                    $newStatus =
                        $this->normalizeStatus(
                            $validated[
                                'status'
                            ]
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | No Change
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $currentStatus
                        ===
                        $newStatus
                    ) {

                        return $lockedPurchaseOrder;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Receiving Protection
                    |--------------------------------------------------------------------------
                    */

                    $hasReceivedItems =
                        $lockedPurchaseOrder
                            ->items()
                            ->where(
                                'received_quantity',
                                '>',
                                0
                            )
                            ->exists();


                    $receivingStatuses = [

                        $this->normalizeStatus(
                            PurchaseOrder::STATUS_PARTIAL
                        ),

                        $this->normalizeStatus(
                            PurchaseOrder::STATUS_RECEIVED
                        ),

                    ];


                    if (
                        $hasReceivedItems
                        ||
                        in_array(
                            $currentStatus,
                            $receivingStatuses,
                            true
                        )
                    ) {

                        throw ValidationException::withMessages([

                            'status' => [
                                'Purchase order status cannot be changed because receiving has already started.',
                            ],

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Cancelled Protection
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $currentStatus
                        ===
                        $this->normalizeStatus(
                            PurchaseOrder::STATUS_CANCELLED
                        )
                    ) {

                        throw ValidationException::withMessages([

                            'status' => [
                                'A cancelled purchase order status cannot be changed.',
                            ],

                        ]);
                    }


                    $lockedPurchaseOrder
                        ->update([

                            'status' =>
                                $validated[
                                    'status'
                                ],

                            'updated_by' =>
                                $request->user()
                                    ->id,

                        ]);


                    return $lockedPurchaseOrder
                        ->fresh();
                }

            );


        $this->loadPurchaseOrderRelations(
            $purchaseOrder
        );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Purchase order status updated successfully.',

            'data' =>
                new PurchaseOrderResource(
                    $purchaseOrder
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Receive Purchase Order
    |--------------------------------------------------------------------------
    */

    public function receive(
        ReceivePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        $receivedPurchaseOrder =
            $this->purchaseReceiveService
                ->receivePurchaseOrder(

                    purchaseOrder:
                        $purchaseOrder,

                    data:
                        $request->validated(),

                    user:
                        $request->user()

                );


        $this->loadPurchaseOrderRelations(
            $receivedPurchaseOrder
        );


        $receivedStatus =
            $this->normalizeStatus(
                $receivedPurchaseOrder
                    ->status
            );


        $fullyReceivedStatus =
            $this->normalizeStatus(
                PurchaseOrder::STATUS_RECEIVED
            );


        $message =
            $receivedStatus
            ===
            $fullyReceivedStatus

                ? 'Purchase order fully received successfully.'

                : 'Purchase order partially received successfully.';


        /*
        |--------------------------------------------------------------------------
        | Latest GRN
        |--------------------------------------------------------------------------
        */

        $latestReceipt =
            $receivedPurchaseOrder
                ->receipts
                ->first();


        return response()->json([

            'success' =>
                true,

            'message' =>
                $message,

            'data' =>
                new PurchaseOrderResource(
                    $receivedPurchaseOrder
                ),

            'receipt' =>
                $latestReceipt
                    ? new PurchaseOrderReceiptResource(
                        $latestReceipt
                    )
                    : null,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Receipt / GRN History
    |--------------------------------------------------------------------------
    */

    public function receipts(
        Request $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $receipts =
            $purchaseOrder
                ->receipts()

                ->with([

                    'items.rawMaterial',

                    'receivedBy',

                    'creator',

                    'updater',

                ])

                ->orderByDesc(
                    'received_at'
                )

                ->orderByDesc(
                    'id'
                )

                ->get();


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Purchase order receipt history loaded successfully.',

            'data' =>
                PurchaseOrderReceiptResource::collection(
                    $receipts
                ),

            'summary' => [

                'purchase_order_id' =>
                    (int) $purchaseOrder
                        ->id,

                'status' =>
                    $purchaseOrder
                        ->status,

                'total_receipts' =>
                    $receipts
                        ->count(),

                /*
                |--------------------------------------------------------------------------
                | Backward-Compatible Raw Quantity Total
                |--------------------------------------------------------------------------
                |
                | Kept for existing frontend compatibility. Because one purchase
                | order may contain different base units, use the unit-aware
                | summary below for meaningful reporting.
                |
                */

                'total_received_quantity' =>
                    round(
                        (float) $receipts
                            ->sum(

                                static function (
                                    $receipt
                                ): float {

                                    return (float) $receipt
                                        ->items
                                        ->sum(
                                            'quantity'
                                        );
                                }

                            ),
                        4
                    ),


                /*
                |--------------------------------------------------------------------------
                | Quantity Summary By Unit
                |--------------------------------------------------------------------------
                */

                'total_received_quantity_by_unit' =>
                    $receipts

                        ->flatMap(
                            static fn ($receipt) =>
                                $receipt->items
                        )

                        ->groupBy(
                            static function ($item): string {

                                $unit =
                                    strtolower(
                                        trim(
                                            (string) (
                                                $item->unit
                                                ?? ''
                                            )
                                        )
                                    );


                                return $unit !== ''
                                    ? $unit
                                    : 'unknown';
                            }
                        )

                        ->map(
                            static function (
                                $items,
                                string $unit
                            ): array {

                                return [

                                    'unit' =>
                                        $unit,

                                    'quantity' =>
                                        round(
                                            (float) $items
                                                ->sum(
                                                    'quantity'
                                                ),
                                            4
                                        ),
                                ];
                            }
                        )

                        ->values()

                        ->all(),

                'total_received_cost' =>
                    round(
                        (float) $receipts
                            ->sum(

                                static function (
                                    $receipt
                                ): float {

                                    return (float) $receipt
                                        ->items
                                        ->sum(
                                            'total_cost'
                                        );
                                }

                            ),
                        4
                    ),

            ],

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Purchase Order
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        $this->ensureManageAccess(
            $request
        );


        $this->purchaseOrderService
            ->deletePurchaseOrder(
                $purchaseOrder
            );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Purchase order deleted successfully.',

            'data' =>
                null,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Load Purchase Order Relationships
    |--------------------------------------------------------------------------
    */

    private function loadPurchaseOrderRelations(
        PurchaseOrder $purchaseOrder
    ): PurchaseOrder {

        return $purchaseOrder
            ->load([

                'supplier',


                'items.rawMaterial',


                /*
                |--------------------------------------------------------------------------
                | Payment History
                |--------------------------------------------------------------------------
                */

                'payments' =>
                    function ($query): void {

                        $query

                            ->with([
                                'creator',
                                'updater',
                            ])

                            ->orderByDesc(
                                'payment_date'
                            )

                            ->orderByDesc(
                                'id'
                            );
                    },


                /*
                |--------------------------------------------------------------------------
                | GRN / Receipt History
                |--------------------------------------------------------------------------
                */

                'receipts' =>
                    function ($query): void {

                        $query

                            ->with([

                                'items.rawMaterial',

                                'receivedBy',

                                'creator',

                                'updater',

                            ])

                            ->orderByDesc(
                                'received_at'
                            )

                            ->orderByDesc(
                                'id'
                            );
                    },


                'orderedBy',

                'creator',

                'updater',

            ]);
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

            'current_page' =>
                $paginator
                    ->currentPage(),

            'last_page' =>
                $paginator
                    ->lastPage(),

            'per_page' =>
                $paginator
                    ->perPage(),

            'total' =>
                $paginator
                    ->total(),

            'from' =>
                $paginator
                    ->firstItem(),

            'to' =>
                $paginator
                    ->lastItem(),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Status
    |--------------------------------------------------------------------------
    */

    private function normalizeStatus(
        ?string $status
    ): string {

        return strtolower(
            trim(
                (string) $status
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | View Permission
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


        $user =
            $request->user();


        $canView =
            $user->hasAnyPermission([

                'inventory.view',

                'inventory.manage',

                'suppliers.manage',

            ]);


        abort_unless(
            $canView,
            403,
            'You do not have permission to view purchase orders.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Manage Permission
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


        $user =
            $request->user();


        $canManage =
            $user->hasAnyPermission([

                'inventory.manage',

                'suppliers.manage',

            ]);


        abort_unless(
            $canManage,
            403,
            'You do not have permission to manage purchase orders.'
        );
    }
}