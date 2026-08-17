<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RawMaterial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseOrderPaymentService $purchaseOrderPaymentService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Get Purchase Orders
    |--------------------------------------------------------------------------
    */

    public function getPurchaseOrders(
        array $filters = []
    ): LengthAwarePaginator {
        $query = PurchaseOrder::query()
            ->with([
                'supplier',
                'items.rawMaterial',
                'orderedBy',
            ]);

        if (!empty($filters['date_from'])) {
            $query->whereDate(
                'order_date',
                '>=',
                $filters['date_from']
            );
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate(
                'order_date',
                '<=',
                $filters['date_to']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Today's Active Orders
        |--------------------------------------------------------------------------
        |
        | Date filter না থাকলে:
        | order_date <= today
        | এবং delivery_date >= today
        | delivery_date null হলে শুধু আজকের order দেখাবে।
        |
        */

        if (
            empty($filters['date_from'])
            &&
            empty($filters['date_to'])
        ) {
            $today = Carbon::today()->toDateString();

            $query->whereDate(
                'order_date',
                '<=',
                $today
            );

            $query->where(
                function ($dateQuery) use ($today): void {
                    $dateQuery
                        ->whereDate(
                            'delivery_date',
                            '>=',
                            $today
                        )
                        ->orWhere(
                            function ($nullDeliveryQuery) use ($today): void {
                                $nullDeliveryQuery
                                    ->whereNull('delivery_date')
                                    ->whereDate(
                                        'order_date',
                                        '=',
                                        $today
                                    );
                            }
                        );
                }
            );
        }

        if (!empty($filters['supplier_id'])) {
            $query->where(
                'supplier_id',
                (int) $filters['supplier_id']
            );
        }

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        $query
            ->orderBy(
                'delivery_date',
                'asc'
            )
            ->orderBy(
                'order_date',
                'desc'
            );

        $perPage = (int) (
            $filters['per_page']
            ?? 10
        );

        $perPage = max(
            1,
            min(
                $perPage,
                100
            )
        );

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }


    /*
    |--------------------------------------------------------------------------
    | Create Purchase Order
    |--------------------------------------------------------------------------
    */

    public function createPurchaseOrder(
        array $data,
        User $user
    ): PurchaseOrder {
        $items = $data['items'] ?? [];

        $this->validatePurchaseItems(
            $items
        );

        return DB::transaction(
            function () use (
                $data,
                $items,
                $user
            ): PurchaseOrder {
                /*
                |------------------------------------------------------------------
                | Prepare / Lock Raw Materials
                |------------------------------------------------------------------
                |
                | RawMaterial is the source of truth for item name and unit.
                |
                */

                $preparedItems =
                    $this->preparePurchaseItems(
                        $items
                    );

                /*
                |------------------------------------------------------------------
                | Calculate Totals
                |------------------------------------------------------------------
                */

                $totals =
                    $this->calculateTotals(
                        items: $preparedItems,
                        data: $data
                    );

                $initialPaidAmount = round(
                    (float) $totals['paid_amount'],
                    2
                );

                /*
                |------------------------------------------------------------------
                | Create Purchase Order Summary
                |------------------------------------------------------------------
                |
                | Payment summary starts at zero. If an advance exists, the
                | PurchaseOrderPaymentService records the ledger and updates the
                | summary afterwards inside this same transaction.
                |
                */

                $purchaseOrder = PurchaseOrder::create([
                    'supplier_id' =>
                        $data['supplier_id'],

                    'order_date' =>
                        $data['order_date'],

                    'delivery_date' =>
                        array_key_exists(
                            'delivery_date',
                            $data
                        )
                            ? $data['delivery_date']
                            : null,

                    'status' =>
                        PurchaseOrder::STATUS_ORDERED,

                    'subtotal' =>
                        $totals['subtotal'],

                    'tax' =>
                        $totals['tax'],

                    'service_charge' =>
                        $totals['service_charge'],

                    'total_amount' =>
                        $totals['total_amount'],

                    'paid_amount' =>
                        0,

                    'due_amount' =>
                        $totals['total_amount'],

                    'payment_method' =>
                        null,

                    'ordered_by' =>
                        $user->id,

                    'notes' =>
                        $this->nullableText(
                            $data['notes']
                            ?? null
                        ),

                    'created_by' =>
                        $user->id,

                    'updated_by' =>
                        $user->id,
                ]);

                /*
                |------------------------------------------------------------------
                | Save Items
                |------------------------------------------------------------------
                */

                $this->saveItems(
                    purchaseOrder: $purchaseOrder,
                    items: $preparedItems
                );

                /*
                |------------------------------------------------------------------
                | Initial / Advance Payment
                |------------------------------------------------------------------
                |
                | Single source of truth:
                | PurchaseOrderPaymentService
                |
                */

                if ($initialPaidAmount > 0) {
                    $this->purchaseOrderPaymentService
                        ->recordPaymentForLockedPurchaseOrder(
                            purchaseOrder: $purchaseOrder,
                            data: [
                                'amount' =>
                                    $initialPaidAmount,

                                'payment_method' =>
                                    $data['payment_method']
                                    ?? null,

                                'payment_date' =>
                                    $data['payment_date']
                                    ?? now()->toDateString(),

                                'transaction_reference' =>
                                    $data['transaction_reference']
                                    ?? null,

                                'notes' =>
                                    $this->nullableText(
                                        $data['payment_notes']
                                        ?? 'Initial payment recorded when purchase order was created.'
                                    ),
                            ],
                            user: $user
                        );
                }

                return $purchaseOrder->fresh([
                    'supplier',
                    'items.rawMaterial',
                    'payments.creator',
                    'payments.updater',
                    'orderedBy',
                    'creator',
                    'updater',
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order
    |--------------------------------------------------------------------------
    */

    public function updatePurchaseOrder(
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
                $lockedPurchaseOrder = PurchaseOrder::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $purchaseOrder->id
                    );

                $this->ensurePurchaseOrderCanBeEdited(
                    $lockedPurchaseOrder
                );

                $this->ensureSupplierCanBeChanged(
                    purchaseOrder: $lockedPurchaseOrder,
                    data: $data
                );

                $itemsWereSubmitted = array_key_exists(
                    'items',
                    $data
                );

                if ($itemsWereSubmitted) {
                    $items = $data['items'];

                    $this->validatePurchaseItems(
                        $items
                    );

                    $preparedItems =
                        $this->preparePurchaseItems(
                            $items
                        );
                } else {
                    /*
                    |--------------------------------------------------------------
                    | Existing Item Snapshots
                    |--------------------------------------------------------------
                    |
                    | No item update was requested. Preserve the historical PO
                    | snapshot values already stored on the order items.
                    |
                    */

                    $preparedItems = $lockedPurchaseOrder
                        ->items()
                        ->get()
                        ->map(
                            static function (
                                PurchaseOrderItem $item
                            ): array {
                                return [
                                    'raw_material_id' =>
                                        (int) $item->raw_material_id,

                                    'item_name' =>
                                        (string) $item->item_name,

                                    'unit' =>
                                        (string) $item->unit,

                                    'quantity' =>
                                        round(
                                            (float) $item->quantity,
                                            4
                                        ),

                                    'unit_price' =>
                                        round(
                                            (float) $item->unit_price,
                                            2
                                        ),
                                ];
                            }
                        )
                        ->values()
                        ->all();
                }

                /*
                |------------------------------------------------------------------
                | Direct Payment Editing Is Not Allowed Here
                |------------------------------------------------------------------
                */

                $calculationData = $data;

                unset(
                    $calculationData['paid_amount'],
                    $calculationData['payment_method'],
                    $calculationData['payment_date'],
                    $calculationData['transaction_reference'],
                    $calculationData['payment_notes']
                );

                $totals = $this->calculateTotals(
                    items: $preparedItems,
                    data: $calculationData,
                    defaultTax:
                        (float) $lockedPurchaseOrder->tax,
                    defaultServiceCharge:
                        (float) $lockedPurchaseOrder->service_charge,
                    defaultPaidAmount:
                        (float) $lockedPurchaseOrder->paid_amount
                );

                $lockedPurchaseOrder->update([
                    'supplier_id' =>
                        array_key_exists(
                            'supplier_id',
                            $data
                        )
                            ? $data['supplier_id']
                            : $lockedPurchaseOrder->supplier_id,

                    'order_date' =>
                        array_key_exists(
                            'order_date',
                            $data
                        )
                            ? $data['order_date']
                            : $lockedPurchaseOrder->order_date,

                    'delivery_date' =>
                        array_key_exists(
                            'delivery_date',
                            $data
                        )
                            ? $data['delivery_date']
                            : $lockedPurchaseOrder->delivery_date,

                    'subtotal' =>
                        $totals['subtotal'],

                    'tax' =>
                        $totals['tax'],

                    'service_charge' =>
                        $totals['service_charge'],

                    'total_amount' =>
                        $totals['total_amount'],

                    'paid_amount' =>
                        $totals['paid_amount'],

                    'due_amount' =>
                        $totals['due_amount'],

                    /* payment_method intentionally preserved */

                    'notes' =>
                        array_key_exists(
                            'notes',
                            $data
                        )
                            ? $this->nullableText(
                                $data['notes']
                            )
                            : $lockedPurchaseOrder->notes,

                    'updated_by' =>
                        $user->id,
                ]);

                if ($itemsWereSubmitted) {
                    PurchaseOrderItem::query()
                        ->where(
                            'purchase_order_id',
                            $lockedPurchaseOrder->id
                        )
                        ->delete();

                    $this->saveItems(
                        purchaseOrder: $lockedPurchaseOrder,
                        items: $preparedItems
                    );
                }

                return $lockedPurchaseOrder->fresh([
                    'supplier',
                    'items.rawMaterial',
                    'payments.creator',
                    'payments.updater',
                    'orderedBy',
                    'creator',
                    'updater',
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Purchase Order
    |--------------------------------------------------------------------------
    */

    public function deletePurchaseOrder(
        PurchaseOrder $purchaseOrder
    ): void {
        DB::transaction(
            function () use (
                $purchaseOrder
            ): void {
                $lockedPurchaseOrder = PurchaseOrder::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $purchaseOrder->id
                    );

                $this->ensurePurchaseOrderCanBeDeleted(
                    $lockedPurchaseOrder
                );

                $lockedPurchaseOrder->delete();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare Purchase Items
    |--------------------------------------------------------------------------
    |
    | Locks raw materials in deterministic ID order to reduce deadlock risk.
    | RawMaterial supplies item_name and unit snapshots.
    |
    */

    private function preparePurchaseItems(
        array $items
    ): array {
        $rawMaterialIds = collect($items)
            ->pluck('raw_material_id')
            ->map(
                static fn (mixed $id): int =>
                    (int) $id
            )
            ->values();

        if (
            $rawMaterialIds->count()
            !==
            $rawMaterialIds->unique()->count()
        ) {
            throw ValidationException::withMessages([
                'items' => [
                    'Duplicate raw materials are not allowed in the same purchase order.',
                ],
            ]);
        }

        $rawMaterials = RawMaterial::query()
            ->whereIn(
                'id',
                $rawMaterialIds
            )
            ->whereNull(
                'deleted_at'
            )
            ->orderBy(
                'id'
            )
            ->lockForUpdate()
            ->get()
            ->keyBy(
                'id'
            );

        if (
            $rawMaterials->count()
            !==
            $rawMaterialIds->unique()->count()
        ) {
            throw ValidationException::withMessages([
                'items' => [
                    'One or more raw materials are unavailable or deleted.',
                ],
            ]);
        }

        $preparedItems = [];

        foreach ($items as $index => $item) {
            /** @var RawMaterial|null $rawMaterial */
            $rawMaterial = $rawMaterials->get(
                (int) $item['raw_material_id']
            );

            if (!$rawMaterial) {
                throw ValidationException::withMessages([
                    "items.{$index}.raw_material_id" => [
                        'The selected raw material is unavailable.',
                    ],
                ]);
            }

            if (!$rawMaterial->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.raw_material_id" => [
                        "The raw material \"{$rawMaterial->material_name}\" is inactive.",
                    ],
                ]);
            }

            $baseUnit = strtolower(
                trim(
                    (string) $rawMaterial->base_unit
                )
            );

            if (
                array_key_exists(
                    'unit',
                    $item
                )
                &&
                $item['unit'] !== null
                &&
                trim(
                    (string) $item['unit']
                ) !== ''
            ) {
                $submittedUnit = strtolower(
                    trim(
                        (string) $item['unit']
                    )
                );

                if ($submittedUnit !== $baseUnit) {
                    throw ValidationException::withMessages([
                        "items.{$index}.unit" => [
                            "Unit mismatch for \"{$rawMaterial->material_name}\". The raw material base unit is {$rawMaterial->base_unit}.",
                        ],
                    ]);
                }
            }

            $quantity = round(
                (float) $item['quantity'],
                4
            );

            $unitPrice = round(
                (float) $item['unit_price'],
                2
            );

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => [
                        'Quantity must be greater than zero.',
                    ],
                ]);
            }

            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => [
                        'Unit price must be zero or greater.',
                    ],
                ]);
            }

            $preparedItems[] = [
                'raw_material_id' =>
                    $rawMaterial->id,

                'item_name' =>
                    trim(
                        (string) $rawMaterial->material_name
                    ),

                'unit' =>
                    $baseUnit,

                'quantity' =>
                    $quantity,

                'unit_price' =>
                    $unitPrice,
            ];
        }

        return $preparedItems;
    }


    /*
    |--------------------------------------------------------------------------
    | Save Purchase Order Items
    |--------------------------------------------------------------------------
    */

    private function saveItems(
        PurchaseOrder $purchaseOrder,
        array $items
    ): void {
        foreach ($items as $item) {
            $quantity = round(
                (float) $item['quantity'],
                4
            );

            $unitPrice = round(
                (float) $item['unit_price'],
                2
            );

            $totalPrice = round(
                $quantity
                *
                $unitPrice,
                2
            );

            PurchaseOrderItem::create([
                'purchase_order_id' =>
                    $purchaseOrder->id,

                'raw_material_id' =>
                    (int) $item['raw_material_id'],

                'item_name' =>
                    trim(
                        (string) $item['item_name']
                    ),

                'unit' =>
                    strtolower(
                        trim(
                            (string) $item['unit']
                        )
                    ),

                'quantity' =>
                    $quantity,

                'received_quantity' =>
                    0,

                'unit_price' =>
                    $unitPrice,

                'total_price' =>
                    $totalPrice,
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Purchase Items
    |--------------------------------------------------------------------------
    */

    private function validatePurchaseItems(
        mixed $items
    ): void {
        if (
            !is_array($items)
            ||
            count($items) === 0
        ) {
            throw ValidationException::withMessages([
                'items' => [
                    'At least one purchase order item is required.',
                ],
            ]);
        }

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                throw ValidationException::withMessages([
                    "items.{$index}" => [
                        'Invalid purchase order item.',
                    ],
                ]);
            }

            if (
                !array_key_exists(
                    'raw_material_id',
                    $item
                )
                ||
                !is_numeric(
                    $item['raw_material_id']
                )
                ||
                (int) $item['raw_material_id'] <= 0
            ) {
                throw ValidationException::withMessages([
                    "items.{$index}.raw_material_id" => [
                        'A valid raw material is required.',
                    ],
                ]);
            }

            if (
                !array_key_exists(
                    'quantity',
                    $item
                )
                ||
                !is_numeric(
                    $item['quantity']
                )
                ||
                (float) $item['quantity'] <= 0
            ) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => [
                        'Quantity must be greater than zero.',
                    ],
                ]);
            }

            if (
                !array_key_exists(
                    'unit_price',
                    $item
                )
                ||
                !is_numeric(
                    $item['unit_price']
                )
                ||
                (float) $item['unit_price'] < 0
            ) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => [
                        'Unit price must be zero or greater.',
                    ],
                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Purchase Order Totals
    |--------------------------------------------------------------------------
    */

    private function calculateTotals(
        array $items,
        array $data,
        float $defaultTax = 0,
        float $defaultServiceCharge = 0,
        float $defaultPaidAmount = 0
    ): array {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $quantity = round(
                (float) $item['quantity'],
                4
            );

            $unitPrice = round(
                (float) $item['unit_price'],
                2
            );

            $lineTotal = round(
                $quantity
                *
                $unitPrice,
                2
            );

            $subtotal += $lineTotal;
        }

        $subtotal = round(
            $subtotal,
            2
        );

        $tax = $this->getNonNegativeAmount(
            data: $data,
            key: 'tax',
            default: $defaultTax
        );

        $serviceCharge = $this->getNonNegativeAmount(
            data: $data,
            key: 'service_charge',
            default: $defaultServiceCharge
        );

        $totalAmount = round(
            $subtotal
            +
            $tax
            +
            $serviceCharge,
            2
        );

        $paidAmount = $this->getNonNegativeAmount(
            data: $data,
            key: 'paid_amount',
            default: $defaultPaidAmount
        );

        if ($paidAmount > $totalAmount) {
            throw ValidationException::withMessages([
                'total_amount' => [
                    'Purchase order total cannot be lower than the amount already paid.',
                ],
            ]);
        }

        $dueAmount = round(
            max(
                0,
                $totalAmount
                -
                $paidAmount
            ),
            2
        );

        return [
            'subtotal' =>
                $subtotal,

            'tax' =>
                $tax,

            'service_charge' =>
                $serviceCharge,

            'total_amount' =>
                $totalAmount,

            'paid_amount' =>
                round(
                    $paidAmount,
                    2
                ),

            'due_amount' =>
                $dueAmount,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Non-Negative Amount
    |--------------------------------------------------------------------------
    */

    private function getNonNegativeAmount(
        array $data,
        string $key,
        float $default = 0
    ): float {
        $value =
            array_key_exists(
                $key,
                $data
            )
            &&
            $data[$key] !== null
            &&
            $data[$key] !== ''
                ? $data[$key]
                : $default;

        if (!is_numeric($value)) {
            throw ValidationException::withMessages([
                $key => [
                    ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $key
                        )
                    )
                    .
                    ' must be a valid number.',
                ],
            ]);
        }

        $amount = round(
            (float) $value,
            2
        );

        if ($amount < 0) {
            throw ValidationException::withMessages([
                $key => [
                    ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $key
                        )
                    )
                    .
                    ' cannot be negative.',
                ],
            ]);
        }

        return $amount;
    }


    /*
    |--------------------------------------------------------------------------
    | Ensure Purchase Order Can Be Edited
    |--------------------------------------------------------------------------
    */

    private function ensurePurchaseOrderCanBeEdited(
        PurchaseOrder $purchaseOrder
    ): void {
        if (
            $purchaseOrder->status
            ===
            PurchaseOrder::STATUS_CANCELLED
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'A cancelled purchase order cannot be edited.',
                ],
            ]);
        }

        if (
            in_array(
                $purchaseOrder->status,
                [
                    PurchaseOrder::STATUS_PARTIAL,
                    PurchaseOrder::STATUS_RECEIVED,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'This purchase order cannot be edited because receiving has already started.',
                ],
            ]);
        }

        if (
            $purchaseOrder
                ->receipts()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'This purchase order cannot be edited because receipt history already exists.',
                ],
            ]);
        }

        if (
            $purchaseOrder
                ->items()
                ->where(
                    'received_quantity',
                    '>',
                    0
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'This purchase order cannot be edited because receiving has already started.',
                ],
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Ensure Supplier Can Be Changed
    |--------------------------------------------------------------------------
    |
    | Payment rows resolve their supplier through the Purchase Order. Changing
    | supplier after payment history exists would corrupt supplier reporting.
    |
    */

    private function ensureSupplierCanBeChanged(
        PurchaseOrder $purchaseOrder,
        array $data
    ): void {
        if (
            !array_key_exists(
                'supplier_id',
                $data
            )
            ||
            $data['supplier_id'] === null
        ) {
            return;
        }

        $newSupplierId = (int) $data['supplier_id'];
        $currentSupplierId = (int) $purchaseOrder->supplier_id;

        if (
            $newSupplierId
            ===
            $currentSupplierId
        ) {
            return;
        }

        if (
            $purchaseOrder
                ->payments()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'supplier_id' => [
                    'Supplier cannot be changed because payment history already exists for this purchase order.',
                ],
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Ensure Purchase Order Can Be Deleted
    |--------------------------------------------------------------------------
    */

    private function ensurePurchaseOrderCanBeDeleted(
        PurchaseOrder $purchaseOrder
    ): void {
        if (
            in_array(
                $purchaseOrder->status,
                [
                    PurchaseOrder::STATUS_PARTIAL,
                    PurchaseOrder::STATUS_RECEIVED,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'This purchase order cannot be deleted because receiving has already started.',
                ],
            ]);
        }

        if (
            $purchaseOrder
                ->receipts()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'This purchase order cannot be deleted because receipt history already exists.',
                ],
            ]);
        }

        if (
            $purchaseOrder
                ->items()
                ->where(
                    'received_quantity',
                    '>',
                    0
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'This purchase order cannot be deleted because receiving has already started.',
                ],
            ]);
        }

        if (
            $purchaseOrder
                ->payments()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'purchase_order' => [
                    'This purchase order cannot be deleted because payment history already exists.',
                ],
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Nullable Text
    |--------------------------------------------------------------------------
    */

    private function nullableText(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }
}