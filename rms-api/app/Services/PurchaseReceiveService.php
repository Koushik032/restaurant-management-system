<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceipt;
use App\Models\PurchaseOrderReceiptItem;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class PurchaseReceiveService
{
    public function __construct(
        private readonly PurchaseOrderPaymentService $purchaseOrderPaymentService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Receive Purchase Order
    |--------------------------------------------------------------------------
    */

    public function receivePurchaseOrder(
        PurchaseOrder $purchaseOrder,
        array $data,
        User $user
    ): PurchaseOrder {

        return DB::transaction(

            function () use (
                $purchaseOrder,
                $data,
                $user
            ): PurchaseOrder {

                /*
                |--------------------------------------------------------------------------
                | Lock Purchase Order
                |--------------------------------------------------------------------------
                */

                $lockedPurchaseOrder =
                    PurchaseOrder::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $purchaseOrder->id
                        );


                /*
                |--------------------------------------------------------------------------
                | Validate Purchase Order Status
                |--------------------------------------------------------------------------
                */

                $this->validatePurchaseOrderStatus(
                    $lockedPurchaseOrder
                );


                /*
                |--------------------------------------------------------------------------
                | Validate Submitted Items
                |--------------------------------------------------------------------------
                */

                $rawSubmittedItems =
                    $data['items']
                    ?? null;


                if (
                    !is_array($rawSubmittedItems)
                    ||
                    count($rawSubmittedItems) === 0
                ) {

                    throw ValidationException::withMessages([
                        'items' => [
                            'At least one purchase order item is required for receiving.',
                        ],
                    ]);
                }


                $submittedItems =
                    collect(
                        $rawSubmittedItems
                    )
                        ->values();


                /*
                |--------------------------------------------------------------------------
                | Validate Item IDs
                |--------------------------------------------------------------------------
                */

                foreach (
                    $submittedItems
                    as
                    $index => $submittedItem
                ) {

                    if (!is_array($submittedItem)) {

                        throw ValidationException::withMessages([
                            "items.{$index}" => [
                                'Invalid receive item.',
                            ],
                        ]);
                    }


                    if (
                        !array_key_exists(
                            'purchase_order_item_id',
                            $submittedItem
                        )
                    ) {

                        throw ValidationException::withMessages([
                            "items.{$index}.purchase_order_item_id" => [
                                'Purchase order item ID is required.',
                            ],
                        ]);
                    }


                    if (
                        !is_numeric(
                            $submittedItem[
                                'purchase_order_item_id'
                            ]
                        )
                        ||
                        (int) $submittedItem[
                            'purchase_order_item_id'
                        ] <= 0
                    ) {

                        throw ValidationException::withMessages([
                            "items.{$index}.purchase_order_item_id" => [
                                'Purchase order item ID must be valid.',
                            ],
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Submitted Item IDs
                |--------------------------------------------------------------------------
                */

                $submittedItemIds =
                    $submittedItems
                        ->pluck(
                            'purchase_order_item_id'
                        )
                        ->map(
                            static fn (
                                mixed $itemId
                            ): int => (int) $itemId
                        )
                        ->values();


                /*
                |--------------------------------------------------------------------------
                | Duplicate Item Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $submittedItemIds->count()
                    !==
                    $submittedItemIds
                        ->unique()
                        ->count()
                ) {

                    throw ValidationException::withMessages([
                        'items' => [
                            'Duplicate purchase order items are not allowed in the same receive request.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Lock Purchase Order Items
                |--------------------------------------------------------------------------
                */

                $purchaseItems =
                    PurchaseOrderItem::query()
                        ->where(
                            'purchase_order_id',
                            $lockedPurchaseOrder->id
                        )
                        ->whereIn(
                            'id',
                            $submittedItemIds
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            'id'
                        );


                /*
                |--------------------------------------------------------------------------
                | Verify Item Ownership
                |--------------------------------------------------------------------------
                */

                if (
                    $purchaseItems->count()
                    !==
                    $submittedItemIds->count()
                ) {

                    throw ValidationException::withMessages([
                        'items' => [
                            'One or more submitted items do not belong to this purchase order.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Pre-Lock Raw Materials In Deterministic Order
                |--------------------------------------------------------------------------
                |
                | Different purchase orders may contain overlapping raw materials.
                | Locking all referenced materials in ascending ID order before
                | warehouse stock processing reduces cross-transaction deadlock risk.
                |
                */

                $rawMaterialIds =
                    $purchaseItems
                        ->pluck(
                            'raw_material_id'
                        )
                        ->filter(
                            static fn (
                                mixed $id
                            ): bool =>
                                is_numeric($id)
                                &&
                                (int) $id > 0
                        )
                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )
                        ->unique()
                        ->sort()
                        ->values();


                if (
                    $rawMaterialIds->isNotEmpty()
                ) {

                    RawMaterial::query()
                        ->whereIn(
                            'id',
                            $rawMaterialIds
                        )
                        ->orderBy(
                            'id'
                        )
                        ->lockForUpdate()
                        ->get();
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize General Notes
                |--------------------------------------------------------------------------
                */

                $generalNotes =
                    $this->normalizeNullableText(
                        $data['notes']
                        ?? null
                    );


                /*
                |--------------------------------------------------------------------------
                | Create GRN / Receipt Header
                |--------------------------------------------------------------------------
                |
                | Header is created only after PO/item validation.
                |
                | If any item receive fails, DB::transaction rolls back the
                | receipt header, receipt items, stock updates and movements.
                |
                */

                /*
                |--------------------------------------------------------------------------
                | Temporary GRN Number
                |--------------------------------------------------------------------------
                |
                | PurchaseOrderReceipt is immutable after creation and receipt_no is
                | required at insert time. We therefore create the row with a temporary
                | unique number inside this transaction, then replace only that temporary
                | value directly in the database before the transaction is committed.
                |
                */

                $temporaryReceiptNo =
                    'TMP-GRN-'
                    .
                    Str::upper(
                        Str::random(
                            20
                        )
                    );


                $receivedAt =
                    now();


                $receipt =
                    PurchaseOrderReceipt::create([

                        'purchase_order_id' =>
                            $lockedPurchaseOrder->id,

                        'receipt_no' =>
                            $temporaryReceiptNo,

                        'received_at' =>
                            $receivedAt,

                        'notes' =>
                            $generalNotes,

                        'received_by' =>
                            $user->id,

                        'created_by' =>
                            $user->id,

                        'updated_by' =>
                            $user->id,

                    ]);


                /*
                |--------------------------------------------------------------------------
                | Generate Final GRN Number
                |--------------------------------------------------------------------------
                |
                | ID-based number is concurrency safe because the receipt ID is unique.
                |
                | Example:
                |
                | GRN-20260811-000001
                |
                | Eloquent update is intentionally not used because receipt history is
                | immutable at the model layer.
                |
                */

                $receiptNo =
                    $this->generateReceiptNumber(
                        $receipt
                    );


                DB::table(
                    'purchase_order_receipts'
                )
                    ->where(
                        'id',
                        $receipt->id
                    )
                    ->update([
                        'receipt_no' =>
                            $receiptNo,
                    ]);


                $receipt->refresh();


                /*
                |--------------------------------------------------------------------------
                | Receive Each Item
                |--------------------------------------------------------------------------
                */

                foreach (
                    $submittedItems
                    as
                    $index => $submittedItem
                ) {

                    $purchaseItemId =
                        (int) $submittedItem[
                            'purchase_order_item_id'
                        ];


                    /** @var PurchaseOrderItem $purchaseItem */
                    $purchaseItem =
                        $purchaseItems->get(
                            $purchaseItemId
                        );


                    $this->receivePurchaseItem(

                        purchaseOrder:
                            $lockedPurchaseOrder,

                        receipt:
                            $receipt,

                        purchaseItem:
                            $purchaseItem,

                        submittedItem:
                            $submittedItem,

                        requestIndex:
                            $index,

                        generalNotes:
                            $generalNotes,

                        user:
                            $user

                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Recalculate Purchase Order Status
                |--------------------------------------------------------------------------
                */

                $lockedPurchaseOrder
                    ->load(
                        'items'
                    );


                $hasReceivedQuantity =
                    $lockedPurchaseOrder
                        ->items
                        ->contains(

                            static function (
                                PurchaseOrderItem $item
                            ): bool {

                                return round(
                                    (float) $item
                                        ->received_quantity,
                                    4
                                ) > 0;
                            }

                        );


                $allItemsFullyReceived =
                    $lockedPurchaseOrder
                        ->items
                        ->isNotEmpty()

                    &&

                    $lockedPurchaseOrder
                        ->items
                        ->every(

                            static function (
                                PurchaseOrderItem $item
                            ): bool {

                                $orderedQuantity =
                                    round(
                                        (float) $item
                                            ->quantity,
                                        4
                                    );


                                $receivedQuantity =
                                    round(
                                        (float) $item
                                            ->received_quantity,
                                        4
                                    );


                                return
                                    $receivedQuantity
                                    >=
                                    $orderedQuantity;
                            }

                        );


                /*
                |--------------------------------------------------------------------------
                | Determine New Status
                |--------------------------------------------------------------------------
                */

                $newStatus =
                    $allItemsFullyReceived

                        ? PurchaseOrder::STATUS_RECEIVED

                        : (
                            $hasReceivedQuantity
                                ? PurchaseOrder::STATUS_PARTIAL
                                : PurchaseOrder::STATUS_ORDERED
                        );


                /*
                |--------------------------------------------------------------------------
                | Update Purchase Order Status
                |--------------------------------------------------------------------------
                */

                $lockedPurchaseOrder
                    ->update([

                        'status' =>
                            $newStatus,

                        'updated_by' =>
                            $user->id,

                    ]);


                /*
                |--------------------------------------------------------------------------
                | Optional Supplier Payment During Receive
                |--------------------------------------------------------------------------
                |
                | Goods receiving and supplier payment are separate business facts,
                | but when the user enters a payment in the receive modal we persist
                | both in THIS SAME transaction. Any payment validation failure rolls
                | back the GRN, warehouse stock, stock movements and payment together.
                |
                */

                if (
                    array_key_exists(
                        'payment',
                        $data
                    )
                    &&
                    $data['payment'] !== null
                ) {

                    $receivePayment =
                        $data['payment'];


                    if (
                        !is_array(
                            $receivePayment
                        )
                    ) {

                        throw ValidationException::withMessages([
                            'payment' => [
                                'Payment details must be a valid object.',
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Delegate All Payment Validation
                    |--------------------------------------------------------------------------
                    |
                    | If a payment object is supplied, it is never silently ignored.
                    | Missing, non-numeric, zero, negative or excessive amounts are
                    | validated by PurchaseOrderPaymentService and roll back this
                    | entire receive transaction on failure.
                    |
                    */

                    $this->purchaseOrderPaymentService
                        ->recordPaymentForLockedPurchaseOrder(

                            purchaseOrder:
                                $lockedPurchaseOrder,

                            data:
                                $receivePayment,

                            user:
                                $user,

                            errorPrefix:
                                'payment.'

                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Return Fresh Purchase Order
                |--------------------------------------------------------------------------
                */

                return $lockedPurchaseOrder
                    ->fresh([

                        'supplier',

                        'items.rawMaterial',

                        'payments.creator',

                        'payments.updater',

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

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Receive Single Purchase Item
    |--------------------------------------------------------------------------
    */

    private function receivePurchaseItem(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderReceipt $receipt,
        PurchaseOrderItem $purchaseItem,
        array $submittedItem,
        int $requestIndex,
        ?string $generalNotes,
        User $user
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Validate Receive Quantity
        |--------------------------------------------------------------------------
        */

        if (
            !array_key_exists(
                'receive_quantity',
                $submittedItem
            )
            ||
            !is_numeric(
                $submittedItem[
                    'receive_quantity'
                ]
            )
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.receive_quantity" => [
                    'Receive quantity must be a valid number.',
                ],
            ]);
        }


        $receiveQuantity =
            round(
                (float) $submittedItem[
                    'receive_quantity'
                ],
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Zero / Negative Receive Protection
        |--------------------------------------------------------------------------
        */

        if (
            $receiveQuantity <= 0
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.receive_quantity" => [
                    'Receive quantity must be greater than zero.',
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Ordered Quantity
        |--------------------------------------------------------------------------
        */

        $orderedQuantity =
            round(
                (float) $purchaseItem
                    ->quantity,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Already Received Quantity
        |--------------------------------------------------------------------------
        */

        $alreadyReceivedQuantity =
            round(
                (float) $purchaseItem
                    ->received_quantity,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Remaining Quantity
        |--------------------------------------------------------------------------
        */

        $remainingQuantity =
            max(

                0,

                round(
                    $orderedQuantity
                    -
                    $alreadyReceivedQuantity,
                    4
                )

            );


        /*
        |--------------------------------------------------------------------------
        | Double Receive Protection
        |--------------------------------------------------------------------------
        */

        if (
            $remainingQuantity <= 0
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.receive_quantity" => [
                    "The item \"{$purchaseItem->item_name}\" has already been fully received.",
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Over Receive Protection
        |--------------------------------------------------------------------------
        */

        if (
            $receiveQuantity
            >
            $remainingQuantity
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.receive_quantity" => [
                    "Only {$remainingQuantity} {$purchaseItem->unit} remains to receive for \"{$purchaseItem->item_name}\".",
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Raw Material ID Validation
        |--------------------------------------------------------------------------
        */

        if (
            !$purchaseItem
                ->raw_material_id
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.purchase_order_item_id" => [
                    "The item \"{$purchaseItem->item_name}\" is not connected to a raw material.",
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Lock Raw Material
        |--------------------------------------------------------------------------
        */

        $rawMaterial =
            RawMaterial::query()
                ->whereKey(
                    $purchaseItem
                        ->raw_material_id
                )
                ->lockForUpdate()
                ->first();


        if (!$rawMaterial) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.purchase_order_item_id" => [
                    "The raw material connected to \"{$purchaseItem->item_name}\" is unavailable.",
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Raw Material Active Check
        |--------------------------------------------------------------------------
        */

        if (
            !$rawMaterial
                ->is_active
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.purchase_order_item_id" => [
                    "The raw material \"{$rawMaterial->material_name}\" is inactive.",
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Unit Compatibility
        |--------------------------------------------------------------------------
        */

        $purchaseUnit =
            strtolower(
                trim(
                    (string) $purchaseItem
                        ->unit
                )
            );


        $baseUnit =
            strtolower(
                trim(
                    (string) $rawMaterial
                        ->base_unit
                )
            );


        if (
            $purchaseUnit
            !==
            $baseUnit
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.purchase_order_item_id" => [
                    "Unit mismatch for \"{$purchaseItem->item_name}\". Purchase unit is {$purchaseItem->unit}, but raw material base unit is {$rawMaterial->base_unit}.",
                ],
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Receive Unit Cost
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                'unit_cost',
                $submittedItem
            )
            &&
            $submittedItem[
                'unit_cost'
            ] !== null
            &&
            $submittedItem[
                'unit_cost'
            ] !== ''
        ) {

            if (
                !is_numeric(
                    $submittedItem[
                        'unit_cost'
                    ]
                )
            ) {

                throw ValidationException::withMessages([
                    "items.{$requestIndex}.unit_cost" => [
                        'Unit cost must be a valid number.',
                    ],
                ]);
            }


            $receivedUnitCost =
                round(
                    (float) $submittedItem[
                        'unit_cost'
                    ],
                    4
                );


            if (
                $receivedUnitCost < 0
            ) {

                throw ValidationException::withMessages([
                    "items.{$requestIndex}.unit_cost" => [
                        'Unit cost cannot be negative.',
                    ],
                ]);
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | Default Unit Cost
            |--------------------------------------------------------------------------
            */

            $receivedUnitCost =
                round(
                    (float) $purchaseItem
                        ->unit_price,
                    4
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Item Notes
        |--------------------------------------------------------------------------
        */

        $itemNotes =
            $this->normalizeNullableText(
                $submittedItem[
                    'notes'
                ]
                ?? null
            );


        /*
        |--------------------------------------------------------------------------
        | Lock / Create Warehouse Stock
        |--------------------------------------------------------------------------
        */

        $warehouseStock =
            WarehouseStock::withTrashed()
                ->where(
                    'raw_material_id',
                    $rawMaterial->id
                )
                ->lockForUpdate()
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Create Warehouse Stock If Missing
        |--------------------------------------------------------------------------
        */

        if (!$warehouseStock) {

            $warehouseStock =
                WarehouseStock::create([

                    'raw_material_id' =>
                        $rawMaterial->id,

                    'quantity' =>
                        0,

                    'average_unit_cost' =>
                        0,

                    'last_received_at' =>
                        null,

                    'created_by' =>
                        $user->id,

                    'updated_by' =>
                        $user->id,

                ]);


            /*
            |--------------------------------------------------------------------------
            | Lock Newly Created Stock
            |--------------------------------------------------------------------------
            */

            $warehouseStock =
                WarehouseStock::query()
                    ->whereKey(
                        $warehouseStock->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();
        }


        /*
        |--------------------------------------------------------------------------
        | Restore Soft Deleted Warehouse Stock
        |--------------------------------------------------------------------------
        */

        if (
            $warehouseStock
                ->trashed()
        ) {

            $warehouseStock
                ->restore();


            $warehouseStock =
                WarehouseStock::query()
                    ->whereKey(
                        $warehouseStock->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();
        }


        /*
        |--------------------------------------------------------------------------
        | Current Stock
        |--------------------------------------------------------------------------
        */

        $quantityBefore =
            round(
                (float) $warehouseStock
                    ->quantity,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | New Stock
        |--------------------------------------------------------------------------
        */

        $quantityAfter =
            round(
                $quantityBefore
                +
                $receiveQuantity,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Existing Average Cost
        |--------------------------------------------------------------------------
        */

        $oldAverageCost =
            round(
                (float) $warehouseStock
                    ->average_unit_cost,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | New Weighted Average Cost
        |--------------------------------------------------------------------------
        */

        $newAverageCost =
            $this->calculateWeightedAverageCost(

                oldQuantity:
                    $quantityBefore,

                oldAverageCost:
                    $oldAverageCost,

                receivedQuantity:
                    $receiveQuantity,

                receivedUnitCost:
                    $receivedUnitCost

            );


        /*
        |--------------------------------------------------------------------------
        | Update Warehouse Stock
        |--------------------------------------------------------------------------
        */

        $warehouseStock
            ->update([

                'quantity' =>
                    $quantityAfter,

                'average_unit_cost' =>
                    $newAverageCost,

                'last_received_at' =>
                    $receipt->received_at,

                'updated_by' =>
                    $user->id,

            ]);


        /*
        |--------------------------------------------------------------------------
        | Update PO Item Received Quantity
        |--------------------------------------------------------------------------
        */

        $newReceivedQuantity =
            round(
                $alreadyReceivedQuantity
                +
                $receiveQuantity,
                4
            );


        if (
            $newReceivedQuantity
            >
            $orderedQuantity
        ) {

            throw ValidationException::withMessages([
                "items.{$requestIndex}.receive_quantity" => [
                    "Receiving this quantity would exceed the ordered quantity for \"{$purchaseItem->item_name}\".",
                ],
            ]);
        }


        $purchaseItem
            ->update([

                'received_quantity' =>
                    $newReceivedQuantity,

            ]);


        /*
        |--------------------------------------------------------------------------
        | Receipt Item Cost
        |--------------------------------------------------------------------------
        */

        $totalCost =
            round(
                $receiveQuantity
                *
                $receivedUnitCost,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Create GRN Item Snapshot
        |--------------------------------------------------------------------------
        */

        PurchaseOrderReceiptItem::create([

            'purchase_order_receipt_id' =>
                $receipt->id,

            'purchase_order_item_id' =>
                $purchaseItem->id,

            'raw_material_id' =>
                $rawMaterial->id,

            'item_name' =>
                $purchaseItem->item_name,

            'unit' =>
                $rawMaterial->base_unit,

            'quantity' =>
                $receiveQuantity,

            'unit_cost' =>
                $receivedUnitCost,

            'total_cost' =>
                $totalCost,

            'notes' =>
                $itemNotes,

        ]);


        /*
        |--------------------------------------------------------------------------
        | Create Immutable Stock Movement
        |--------------------------------------------------------------------------
        |
        | Movement now references the exact GRN, not only the PO.
        |
        */

        StockMovement::create([

            'raw_material_id' =>
                $rawMaterial->id,

            'location' =>
                StockMovement::LOCATION_WAREHOUSE,

            'movement_type' =>
                StockMovement::TYPE_PURCHASE_RECEIVE,

            'quantity' =>
                $receiveQuantity,

            'quantity_before' =>
                $quantityBefore,

            'quantity_after' =>
                $quantityAfter,

            'unit_cost' =>
                $receivedUnitCost,

            'reference_type' =>
                PurchaseOrderReceipt::class,

            'reference_id' =>
                $receipt->id,

            'unit' =>
                $rawMaterial->base_unit,

            'notes' =>
                $this->buildMovementNotes(

                    purchaseOrder:
                        $purchaseOrder,

                    receipt:
                        $receipt,

                    purchaseItem:
                        $purchaseItem,

                    itemNotes:
                        $itemNotes,

                    generalNotes:
                        $generalNotes

                ),

            'created_by' =>
                $user->id,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Receipt / GRN Number
    |--------------------------------------------------------------------------
    */

    private function generateReceiptNumber(
        PurchaseOrderReceipt $receipt
    ): string {

        $datePart =
            $receipt
                ->received_at
                ?->format(
                    'Ymd'
                )
            ??
            now()
                ->format(
                    'Ymd'
                );


        return sprintf(
            'GRN-%s-%06d',
            $datePart,
            $receipt->id
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Weighted Average Unit Cost
    |--------------------------------------------------------------------------
    */

    private function calculateWeightedAverageCost(
        float $oldQuantity,
        float $oldAverageCost,
        float $receivedQuantity,
        float $receivedUnitCost
    ): float {

        $newQuantity =
            $oldQuantity
            +
            $receivedQuantity;


        if (
            $newQuantity <= 0
        ) {

            return 0;
        }


        $oldStockValue =
            $oldQuantity
            *
            $oldAverageCost;


        $receivedStockValue =
            $receivedQuantity
            *
            $receivedUnitCost;


        return round(

            (
                $oldStockValue
                +
                $receivedStockValue
            )
            /
            $newQuantity,

            4

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Status Validation
    |--------------------------------------------------------------------------
    */

    private function validatePurchaseOrderStatus(
        PurchaseOrder $purchaseOrder
    ): void {

        $allowedStatuses = [

            PurchaseOrder::STATUS_ORDERED,

            PurchaseOrder::STATUS_PARTIAL,

        ];


        if (
            in_array(
                $purchaseOrder->status,
                $allowedStatuses,
                true
            )
        ) {

            return;
        }


        throw ValidationException::withMessages([

            'purchase_order' => [

                match (
                    $purchaseOrder->status
                ) {

                    PurchaseOrder::STATUS_RECEIVED =>
                        'This purchase order has already been fully received.',


                    PurchaseOrder::STATUS_CANCELLED =>
                        'A cancelled purchase order cannot be received.',


                    default =>
                        'This purchase order is not available for receiving.',

                },

            ],

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Movement Notes
    |--------------------------------------------------------------------------
    */

    private function buildMovementNotes(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderReceipt $receipt,
        PurchaseOrderItem $purchaseItem,
        mixed $itemNotes,
        mixed $generalNotes
    ): string {

        $notes = [

            "GRN: {$receipt->receipt_no}",

            "Purchase order #{$purchaseOrder->id}",

            "Item: {$purchaseItem->item_name}",

        ];


        /*
        |--------------------------------------------------------------------------
        | Item Note
        |--------------------------------------------------------------------------
        */

        if (
            is_string(
                $itemNotes
            )
            &&
            trim(
                $itemNotes
            ) !== ''
        ) {

            $notes[] =
                'Item note: '
                .
                trim(
                    $itemNotes
                );
        }


        /*
        |--------------------------------------------------------------------------
        | General Receive Note
        |--------------------------------------------------------------------------
        */

        if (
            is_string(
                $generalNotes
            )
            &&
            trim(
                $generalNotes
            ) !== ''
        ) {

            $notes[] =
                'Receive note: '
                .
                trim(
                    $generalNotes
                );
        }


        return mb_substr(

            implode(
                ' | ',
                $notes
            ),

            0,

            2000

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Nullable Text
    |--------------------------------------------------------------------------
    */

    private function normalizeNullableText(
        mixed $value
    ): ?string {

        if (
            !is_string(
                $value
            )
        ) {

            return null;
        }


        $value =
            trim(
                $value
            );


        return $value !== ''
            ? $value
            : null;
    }
}