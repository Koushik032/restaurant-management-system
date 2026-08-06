<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{

    /**
 * Load all required information for editing an order.
 */
public function getEditOptions(
    Order $order,
    ?int $userId = null
): array {
    /*
    |--------------------------------------------------------------------------
    | Load current order relationships
    |--------------------------------------------------------------------------
    */

    $order->load([
        'customer',
        'primaryTable',
        'tables',
        'items.addons',
        'creator',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Prevent finalized orders from being edited
    |--------------------------------------------------------------------------
    */

    if ($order->isFinalized()) {
        throw ValidationException::withMessages([
            'order' => [
                'A completed or canceled order cannot be edited.',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Current order table IDs
    |--------------------------------------------------------------------------
    |
    | Current tables are occupied, but they must still appear on the edit page.
    |
    */

    $currentTableIds = $order->tables
        ->pluck('id')
        ->map(
            static fn (mixed $id): int =>
                (int) $id
        )
        ->unique()
        ->values();

    if (
        $currentTableIds->isEmpty() &&
        $order->restaurant_table_id
    ) {
        $currentTableIds = collect([
            (int) $order->restaurant_table_id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Available tables plus current order tables
    |--------------------------------------------------------------------------
    */

    $tables = RestaurantTable::query()
        ->where(
            function (
                Builder $query
            ) use (
                $currentTableIds
            ): void {
                $query
                    ->where(
                        'status',
                        RestaurantTable::STATUS_AVAILABLE
                    );

                if ($currentTableIds->isNotEmpty()) {
                    $query->orWhereIn(
                        'id',
                        $currentTableIds
                    );
                }
            }
        )
        ->orderBy('table_name')
        ->get([
            'id',
            'table_name',
            'capacity',
            'section',
            'status',
            'merged_with_id',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Available menu items and variants
    |--------------------------------------------------------------------------
    */

    $menuItems = MenuItem::query()
        ->available()
        ->with([
            'variants' => function (
                $query
            ): void {
                $query
                    ->where(
                        'is_available',
                        true
                    )
                    ->orderBy('price');
            },
        ])
        ->orderBy('menu_name')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Global available add-ons
    |--------------------------------------------------------------------------
    */

    $addons = AddOn::query()
        ->available()
        ->orderBy('add_on_name')
        ->get([
            'id',
            'add_on_name',
            'price',
            'description',
            'is_available',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Status options
    |--------------------------------------------------------------------------
    */

    $statuses = [
        [
            'value' =>
                Order::STATUS_PENDING,

            'label' =>
                'Pending',
        ],
        [
            'value' =>
                Order::STATUS_PREPARING,

            'label' =>
                'Preparing',
        ],
        [
            'value' =>
                Order::STATUS_READY,

            'label' =>
                'Ready',
        ],
        [
            'value' =>
                Order::STATUS_SERVED,

            'label' =>
                'Served',
        ],
    ];

    /*
    |--------------------------------------------------------------------------
    | Current waiter
    |--------------------------------------------------------------------------
    */

    $waiter = $order->creator;

    if (!$waiter && $userId) {
        $waiter = \App\Models\User::query()
            ->find($userId);
    }

    return [
        'order' =>
            $order,

        'tables' =>
            $tables,

        'merge_tables' =>
            $tables,

        'menu_items' =>
            $menuItems,

        'addons' =>
            $addons,

        'statuses' =>
            $statuses,

        'waiter' => [
            'id' =>
                $waiter?->id,

            'name' =>
                $waiter?->name,
        ],
    ];
}
    /**
     * Create a complete dine-in order.
     */
    /**
 * Create a new dine-in order.
 */
public function createOrder(
    array $data,
    ?int $userId
): Order {
    return DB::transaction(
        function () use (
            $data,
            $userId
        ): Order {
            /*
            |--------------------------------------------------------------------------
            | Lock and validate primary table
            |--------------------------------------------------------------------------
            */

            $primaryTable = RestaurantTable::query()
                ->lockForUpdate()
                ->findOrFail(
                    (int) $data['restaurant_table_id']
                );

            $this->ensureTableIsAvailable(
                table: $primaryTable,
                field: 'restaurant_table_id'
            );

            /*
            |--------------------------------------------------------------------------
            | Lock and validate merged tables
            |--------------------------------------------------------------------------
            */

            $mergedTableIds = collect(
                $data['merged_table_ids'] ?? []
            )
                ->filter(
                    static fn (mixed $id): bool =>
                        $id !== null &&
                        $id !== ''
                )
                ->map(
                    static fn (mixed $id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

            if (
                $mergedTableIds->contains(
                    (int) $primaryTable->id
                )
            ) {
                throw ValidationException::withMessages([
                    'merged_table_ids' => [
                        'The primary table cannot also be selected as a merged table.',
                    ],
                ]);
            }

            $mergedTables = $this->loadMergedTables(
                $mergedTableIds
            );

            /*
            |--------------------------------------------------------------------------
            | Resolve customer
            |--------------------------------------------------------------------------
            */

            $customer = $this->resolveCustomer(
                $data
            );

            /*
            |--------------------------------------------------------------------------
            | Prepare and validate order items
            |--------------------------------------------------------------------------
            */

            $preparedItems = $this->prepareOrderItems(
                $data['items']
            );

            $subtotal = round(
                (float) $preparedItems->sum(
                    'line_total'
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Calculate discount
            |--------------------------------------------------------------------------
            */

            $discountAmount = round(
                max(
                    0,
                    (float) (
                        $data['discount_amount'] ?? 0
                    )
                ),
                2
            );

            if ($discountAmount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_amount' => [
                        'The discount cannot be greater than the subtotal.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate tax, service charge and total
            |--------------------------------------------------------------------------
            |
            | Tax and service charge remain zero until dynamic restaurant
            | settings are added.
            |
            */

            $taxAmount = 0.00;
            $serviceCharge = 0.00;

            $totalAmount = round(
                max(
                    0,
                    $subtotal
                    - $discountAmount
                    + $taxAmount
                    + $serviceCharge
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Payment calculation
            |--------------------------------------------------------------------------
            */

            $paidAmount = round(
                max(
                    0,
                    (float) (
                        $data['paid_amount'] ?? 0
                    )
                ),
                2
            );

            if ($paidAmount > $totalAmount) {
                throw ValidationException::withMessages([
                    'paid_amount' => [
                        'Paid amount cannot be greater than the total amount.',
                    ],
                ]);
            }

            $dueAmount = round(
                max(
                    0,
                    $totalAmount - $paidAmount
                ),
                2
            );

            $paymentStatus = match (true) {
                $paidAmount <= 0 =>
                    Order::PAYMENT_DUE,

                $dueAmount <= 0 =>
                    Order::PAYMENT_PAID,

                default =>
                    Order::PAYMENT_PARTIALLY_PAID,
            };

            $paymentMethod = $paidAmount > 0
                ? $this->nullableString(
                    $data['payment_method'] ?? null
                )
                : null;

            $paymentReference = $paidAmount > 0
                ? $this->nullableString(
                    $data['payment_reference'] ?? null
                )
                : null;

            /*
            |--------------------------------------------------------------------------
            | Validate payment method
            |--------------------------------------------------------------------------
            */

            if (
                $paidAmount > 0 &&
                blank($paymentMethod)
            ) {
                throw ValidationException::withMessages([
                    'payment_method' => [
                        'Payment method is required when a paid amount is provided.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->create([
                    'order_number' =>
                        $this->generateOrderNumber(),

                    'customer_id' =>
                        $customer?->id,

                    'customer_name' =>
                        $customer?->name
                        ?? $this->nullableString(
                            $data['customer_name'] ?? null
                        ),

                    'customer_phone' =>
                        $customer?->phone
                        ?? $this->nullableString(
                            $data['customer_phone'] ?? null
                        ),

                    'customer_email' =>
                        $customer?->email
                        ?? $this->nullableString(
                            $data['customer_email'] ?? null
                        ),

                    'restaurant_table_id' =>
                        $primaryTable->id,

                    'status' =>
                        $data['status']
                        ?? Order::STATUS_PENDING,

                    'subtotal' =>
                        $subtotal,

                    'discount_amount' =>
                        $discountAmount,

                    'tax_amount' =>
                        $taxAmount,

                    'service_charge' =>
                        $serviceCharge,

                    'total_amount' =>
                        $totalAmount,

                    'paid_amount' =>
                        $paidAmount,

                    'due_amount' =>
                        $dueAmount,

                    'payment_status' =>
                        $paymentStatus,

                    'payment_method' =>
                        $paymentMethod,

                    'payment_breakdown' =>
                        null,

                    'payment_reference' =>
                        $paymentReference,

                    'order_note' =>
                        $this->nullableString(
                            $data['order_note'] ?? null
                        ),

                    'kitchen_note' =>
                        null,

                    'sent_to_kitchen_at' =>
                        null,

                    'is_customer_spend_recorded' =>
                        false,

                    'customer_spend_recorded_at' =>
                        null,

                    'created_by' =>
                        $userId,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Continue in Part 2
            |--------------------------------------------------------------------------
            */
                        /*
            |--------------------------------------------------------------------------
            | Attach primary and merged tables
            |--------------------------------------------------------------------------
            */

            $tableSyncData = [
                (int) $primaryTable->id => [
                    'is_primary' => true,
                ],
            ];

            foreach (
                $mergedTables
                as $mergedTable
            ) {
                $tableSyncData[
                    (int) $mergedTable->id
                ] = [
                    'is_primary' => false,
                ];
            }

            $order->tables()->sync(
                $tableSyncData
            );

            /*
            |--------------------------------------------------------------------------
            | Save order items and add-ons
            |--------------------------------------------------------------------------
            */

            foreach (
                $preparedItems
                as $preparedItem
            ) {
                $orderItem = $order
                    ->items()
                    ->create([
                        'menu_item_id' =>
                            $preparedItem[
                                'menu_item_id'
                            ],

                        'menu_item_variant_id' =>
                            $preparedItem[
                                'menu_item_variant_id'
                            ],

                        'item_name' =>
                            $preparedItem[
                                'item_name'
                            ],

                        'variant_name' =>
                            $preparedItem[
                                'variant_name'
                            ],

                        'unit_price' =>
                            $preparedItem[
                                'unit_price'
                            ],

                        'quantity' =>
                            $preparedItem[
                                'quantity'
                            ],

                        'addon_total' =>
                            $preparedItem[
                                'addon_total'
                            ],

                        'line_total' =>
                            $preparedItem[
                                'line_total'
                            ],

                        'status' =>
                            Order::STATUS_PENDING,

                        'kitchen_note' =>
                            $preparedItem[
                                'kitchen_note'
                            ],
                    ]);

                foreach (
                    $preparedItem['addons']
                    as $addon
                ) {
                    $orderItem
                        ->addons()
                        ->create([
                            'menu_addon_id' =>
                                $addon['id'],

                            'addon_name' =>
                                $addon[
                                    'addon_name'
                                ],

                            'unit_price' =>
                                $addon[
                                    'unit_price'
                                ],

                            /*
                             * Each selected add-on is applied
                             * once for every menu item quantity.
                             */
                            'quantity' =>
                                $preparedItem[
                                    'quantity'
                                ],

                            'total_price' =>
                                $addon[
                                    'total_price'
                                ],
                        ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Collect all selected table IDs
            |--------------------------------------------------------------------------
            */

            $allSelectedTableIds = collect([
                (int) $primaryTable->id,
            ])
                ->merge(
                    $mergedTables
                        ->pluck('id')
                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )
                )
                ->unique()
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Mark selected tables occupied
            |--------------------------------------------------------------------------
            */

            RestaurantTable::query()
                ->whereIn(
                    'id',
                    $allSelectedTableIds
                )
                ->update([
                    'status' =>
                        RestaurantTable::STATUS_OCCUPIED,

                    'reservation_start_at' =>
                        null,

                    'reservation_end_at' =>
                        null,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Save physical table merge relationship
            |--------------------------------------------------------------------------
            */

            if (
                $mergedTables->isNotEmpty()
            ) {
                RestaurantTable::query()
                    ->whereIn(
                        'id',
                        $mergedTables->pluck(
                            'id'
                        )
                    )
                    ->update([
                        'merged_with_id' =>
                            $primaryTable->id,
                    ]);

                $primaryTable->update([
                    'merged_with_id' =>
                        null,
                ]);
            } else {
                /*
                 * Ensure the primary table is not left linked
                 * to an older physical merge relationship.
                 */
                $primaryTable->update([
                    'merged_with_id' =>
                        null,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Continue in Part 3
            |--------------------------------------------------------------------------
            */
                        /*
            |--------------------------------------------------------------------------
            | Update customer statistics
            |--------------------------------------------------------------------------
            */

            if ($customer) {

                $customer->update([

                    'last_visit_at' =>
                        now(),

                    'total_orders' =>
                        Order::query()
                            ->where(
                                'customer_id',
                                $customer->id
                            )
                            ->count(),

                ]);

            }


            /*
            |--------------------------------------------------------------------------
            | Save Initial Payment History
            |--------------------------------------------------------------------------
            */

            if ($paidAmount > 0) {

                $order
                    ->payments()
                    ->create([

                        'amount' =>
                            $paidAmount,

                        'payment_method' =>
                            $paymentMethod,

                        'reference' =>
                            $paymentReference,

                        'note' =>
                            'Initial payment during order creation.',

                        'received_by' =>
                            $userId,

                    ]);

            }


            /*
            |--------------------------------------------------------------------------
            | Load relationships
            |--------------------------------------------------------------------------
            */

            $order->load([

                'customer',

                'primaryTable',

                'tables',

                'items.addons',

                'payments.receiver',

                'creator',

            ]);


            /*
            |--------------------------------------------------------------------------
            | Return fresh order
            |--------------------------------------------------------------------------
            */

            return $order;

        }
    );
}

/**
 * Update an existing active dine-in order.
 */
public function updateOrder(
    Order $order,
    array $data,
    ?int $userId
): Order {
    return DB::transaction(
        function () use (
            $order,
            $data,
            $userId
        ): Order {
            /*
            |--------------------------------------------------------------------------
            | Lock existing order
            |--------------------------------------------------------------------------
            */

            $lockedOrder = Order::query()
                ->with([
                    'customer',
                    'tables',
                    'items.addons',
                    'payments',
                ])
                ->lockForUpdate()
                ->findOrFail(
                    $order->id
                );

            /*
            |--------------------------------------------------------------------------
            | Prevent editing finalized orders
            |--------------------------------------------------------------------------
            */

            if ($lockedOrder->isFinalized()) {
                throw ValidationException::withMessages([
                    'order' => [
                        'A completed or canceled order cannot be edited.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Current assigned tables
            |--------------------------------------------------------------------------
            */

            $currentTableIds = $lockedOrder
                ->tables
                ->pluck('id')
                ->map(
                    static fn (mixed $id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

            if (
                $currentTableIds->isEmpty() &&
                $lockedOrder->restaurant_table_id
            ) {
                $currentTableIds = collect([
                    (int) $lockedOrder
                        ->restaurant_table_id,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Lock and validate new primary table
            |--------------------------------------------------------------------------
            */

            $primaryTableId = (int)
                $data['restaurant_table_id'];

            $primaryTable = RestaurantTable::query()
                ->lockForUpdate()
                ->findOrFail(
                    $primaryTableId
                );

            /*
            |--------------------------------------------------------------------------
            | Prepare merged table IDs
            |--------------------------------------------------------------------------
            */

            $mergedTableIds = collect(
                $data['merged_table_ids'] ?? []
            )
                ->filter(
                    static fn (mixed $id): bool =>
                        $id !== null &&
                        $id !== ''
                )
                ->map(
                    static fn (mixed $id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

            if (
                $mergedTableIds->contains(
                    $primaryTableId
                )
            ) {
                throw ValidationException::withMessages([
                    'merged_table_ids' => [
                        'The primary table cannot also be selected as a merged table.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Load and lock merged tables
            |--------------------------------------------------------------------------
            */

            $mergedTables = $mergedTableIds->isEmpty()
                ? collect()
                : RestaurantTable::query()
                    ->whereIn(
                        'id',
                        $mergedTableIds
                    )
                    ->lockForUpdate()
                    ->get();

            if (
                $mergedTables->count() !==
                $mergedTableIds->count()
            ) {
                throw ValidationException::withMessages([
                    'merged_table_ids' => [
                        'One or more selected merged tables no longer exist.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate selected tables
            |--------------------------------------------------------------------------
            |
            | Existing tables assigned to this order may remain occupied.
            | Newly selected tables must be available.
            |
            */

            $this->ensureTableCanBeUsedForUpdate(
                table: $primaryTable,
                field: 'restaurant_table_id',
                order: $lockedOrder,
                currentTableIds: $currentTableIds
            );

            foreach (
                $mergedTables
                as $mergedTable
            ) {
                $this->ensureTableCanBeUsedForUpdate(
                    table: $mergedTable,
                    field: 'merged_table_ids',
                    order: $lockedOrder,
                    currentTableIds: $currentTableIds
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Resolve customer
            |--------------------------------------------------------------------------
            */

            $oldCustomerId =
                $lockedOrder->customer_id;

            $customer = $this->resolveCustomer(
                $data
            );

            $newCustomerId =
                $customer?->id;

            /*
            |--------------------------------------------------------------------------
            | Prepare and validate order items
            |--------------------------------------------------------------------------
            */

            $preparedItems = $this->prepareOrderItems(
                $data['items']
            );

            $subtotal = round(
                (float) $preparedItems->sum(
                    'line_total'
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Calculate discount
            |--------------------------------------------------------------------------
            */

            $discountAmount = round(
                max(
                    0,
                    (float) (
                        $data['discount_amount'] ?? 0
                    )
                ),
                2
            );

            if ($discountAmount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_amount' => [
                        'The discount cannot be greater than the subtotal.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate tax, service charge and total
            |--------------------------------------------------------------------------
            |
            | Tax and service charge remain zero until dynamic restaurant
            | settings are implemented.
            |
            */

            $taxAmount = 0.00;
            $serviceCharge = 0.00;

            $totalAmount = round(
                max(
                    0,
                    $subtotal
                    - $discountAmount
                    + $taxAmount
                    + $serviceCharge
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Existing payment history total
            |--------------------------------------------------------------------------
            |
            | Payment history is the audit source of truth. The paid amount cannot
            | be reduced below money that has already been recorded as received.
            |
            */

            $recordedPaidAmount = round(
                (float) $lockedOrder
                    ->payments()
                    ->sum('amount'),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Requested cumulative paid amount
            |--------------------------------------------------------------------------
            */

            $paidAmount = round(
                max(
                    0,
                    (float) (
                        $data['paid_amount']
                        ?? $recordedPaidAmount
                    )
                ),
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Validate payment against recorded history and new total
            |--------------------------------------------------------------------------
            */

            if ($paidAmount < $recordedPaidAmount) {
                throw ValidationException::withMessages([
                    'paid_amount' => [
                        'Paid amount cannot be less than the amount already recorded in payment history.',
                    ],
                ]);
            }

            if ($recordedPaidAmount > $totalAmount) {
                throw ValidationException::withMessages([
                    'total_amount' => [
                        'The updated order total cannot be less than the amount already paid.',
                    ],
                ]);
            }

            if ($paidAmount > $totalAmount) {
                throw ValidationException::withMessages([
                    'paid_amount' => [
                        'Paid amount cannot be greater than the total amount.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate additional payment
            |--------------------------------------------------------------------------
            */

            $newPaymentAmount = round(
                $paidAmount - $recordedPaidAmount,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Resolve payment method and reference
            |--------------------------------------------------------------------------
            */

            $paymentMethod = $newPaymentAmount > 0
                ? $this->nullableString(
                    $data['payment_method'] ?? null
                )
                : $lockedOrder->payment_method;

            $paymentReference = $newPaymentAmount > 0
                ? $this->nullableString(
                    $data['payment_reference'] ?? null
                )
                : $lockedOrder->payment_reference;

            if (
                $newPaymentAmount > 0 &&
                blank($paymentMethod)
            ) {
                throw ValidationException::withMessages([
                    'payment_method' => [
                        'Payment method is required when an additional payment is provided.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate payment summary
            |--------------------------------------------------------------------------
            */

            $dueAmount = round(
                max(
                    0,
                    $totalAmount - $paidAmount
                ),
                2
            );

            $paymentStatus = match (true) {
                $paidAmount <= 0 =>
                    Order::PAYMENT_DUE,

                $dueAmount <= 0 =>
                    Order::PAYMENT_PAID,

                default =>
                    Order::PAYMENT_PARTIALLY_PAID,
            };

            /*
            |--------------------------------------------------------------------------
            | Continue in Part 2
            |--------------------------------------------------------------------------
            */
                        /*
            |--------------------------------------------------------------------------
            | Resolve order status and timestamps
            |--------------------------------------------------------------------------
            */

            $newStatus = $data['status']
                ?? $lockedOrder->status;

            $statusTimestamps =
                $this->buildOrderStatusTimestamps(
                    order: $lockedOrder,
                    status: $newStatus
                );

            /*
            |--------------------------------------------------------------------------
            | Update main order
            |--------------------------------------------------------------------------
            */

            $lockedOrder->update([
                'customer_id' =>
                    $newCustomerId,

                'customer_name' =>
                    $customer?->name
                    ?? $this->nullableString(
                        $data['customer_name']
                        ?? null
                    ),

                'customer_phone' =>
                    $customer?->phone
                    ?? $this->nullableString(
                        $data['customer_phone']
                        ?? null
                    ),

                'customer_email' =>
                    $customer?->email
                    ?? $this->nullableString(
                        $data['customer_email']
                        ?? null
                    ),

                'restaurant_table_id' =>
                    $primaryTable->id,

                'status' =>
                    $newStatus,

                'subtotal' =>
                    $subtotal,

                'discount_amount' =>
                    $discountAmount,

                'tax_amount' =>
                    $taxAmount,

                'service_charge' =>
                    $serviceCharge,

                'total_amount' =>
                    $totalAmount,

                'paid_amount' =>
                    $paidAmount,

                'due_amount' =>
                    $dueAmount,

                'payment_status' =>
                    $paymentStatus,

                /*
                 * Keep the latest successful payment method
                 * as the order-level payment summary.
                 */
                'payment_method' =>
                    $paymentMethod,

                'payment_breakdown' =>
                    null,

                'payment_reference' =>
                    $paymentReference,

                'order_note' =>
                    $this->nullableString(
                        $data['order_note']
                        ?? null
                    ),

                /*
                 * Preserve the original creator.
                 * Use the current user only when the creator is missing.
                 */
                'created_by' =>
                    $lockedOrder->created_by
                    ?? $userId,

                ...$statusTimestamps,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Save additional payment history
            |--------------------------------------------------------------------------
            |
            | Only the amount added beyond the existing payment-history total is
            | inserted. Editing an order without increasing paid_amount will not
            | create a duplicate payment record.
            |
            */

            if ($newPaymentAmount > 0) {
                $lockedOrder
                    ->payments()
                    ->create([
                        'amount' =>
                            $newPaymentAmount,

                        'payment_method' =>
                            $paymentMethod,

                        'reference' =>
                            $paymentReference,

                        'note' =>
                            'Additional payment received during order update.',

                        'received_by' =>
                            $userId,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Verify and synchronise payment summary
            |--------------------------------------------------------------------------
            |
            | The database payment-history total is recalculated after inserting
            | the additional payment. This prevents the order summary and payment
            | history from becoming inconsistent.
            |
            */

            $updatedRecordedPaidAmount = round(
                (float) $lockedOrder
                    ->payments()
                    ->sum('amount'),
                2
            );

            $updatedDueAmount = round(
                max(
                    0,
                    $totalAmount
                    - $updatedRecordedPaidAmount
                ),
                2
            );

            $updatedPaymentStatus = match (true) {
                $updatedRecordedPaidAmount <= 0 =>
                    Order::PAYMENT_DUE,

                $updatedDueAmount <= 0 =>
                    Order::PAYMENT_PAID,

                default =>
                    Order::PAYMENT_PARTIALLY_PAID,
            };

            /*
             * Normally these values already match the first update. This second
             * update guarantees that payment history remains the source of truth.
             */
            $lockedOrder->update([
                'paid_amount' =>
                    $updatedRecordedPaidAmount,

                'due_amount' =>
                    $updatedDueAmount,

                'payment_status' =>
                    $updatedPaymentStatus,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Build newly selected table IDs
            |--------------------------------------------------------------------------
            */

            $newSelectedTableIds = collect([
                (int) $primaryTable->id,
            ])
                ->merge(
                    $mergedTables
                        ->pluck('id')
                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )
                )
                ->unique()
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Determine removed tables
            |--------------------------------------------------------------------------
            */

            $removedTableIds = $currentTableIds
                ->diff(
                    $newSelectedTableIds
                )
                ->values();

            /*
            |--------------------------------------------------------------------------
            | Release removed tables
            |--------------------------------------------------------------------------
            */

            if ($removedTableIds->isNotEmpty()) {
                RestaurantTable::query()
                    ->whereIn(
                        'id',
                        $removedTableIds
                    )
                    ->lockForUpdate()
                    ->update([
                        'status' =>
                            RestaurantTable::STATUS_AVAILABLE,

                        'merged_with_id' =>
                            null,

                        'reservation_start_at' =>
                            null,

                        'reservation_end_at' =>
                            null,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Continue in Part 3
            |--------------------------------------------------------------------------
            */
                        /*
            |--------------------------------------------------------------------------
            | Occupy selected tables
            |--------------------------------------------------------------------------
            */

            RestaurantTable::query()
                ->whereIn(
                    'id',
                    $newSelectedTableIds
                )
                ->lockForUpdate()
                ->update([
                    'status' =>
                        RestaurantTable::STATUS_OCCUPIED,

                    'merged_with_id' =>
                        null,

                    'reservation_start_at' =>
                        null,

                    'reservation_end_at' =>
                        null,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Rebuild physical merge relationship
            |--------------------------------------------------------------------------
            */

            if ($mergedTables->isNotEmpty()) {
                RestaurantTable::query()
                    ->whereIn(
                        'id',
                        $mergedTableIds
                    )
                    ->update([
                        'merged_with_id' =>
                            $primaryTable->id,
                    ]);
            }

            /*
             * Ensure the primary table is never marked as
             * merged into another table.
             */
            $primaryTable->update([
                'merged_with_id' =>
                    null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Sync order-table pivot
            |--------------------------------------------------------------------------
            */

            $tableSyncData = [
                (int) $primaryTable->id => [
                    'is_primary' => true,
                ],
            ];

            foreach (
                $mergedTables
                as $mergedTable
            ) {
                $tableSyncData[
                    (int) $mergedTable->id
                ] = [
                    'is_primary' => false,
                ];
            }

            $lockedOrder
                ->tables()
                ->sync(
                    $tableSyncData
                );

            /*
            |--------------------------------------------------------------------------
            | Remove existing order items and add-ons
            |--------------------------------------------------------------------------
            |
            | The edit form submits the complete updated order state.
            | Existing item snapshots are therefore replaced.
            |
            */

            $existingItems = $lockedOrder
                ->items()
                ->with('addons')
                ->lockForUpdate()
                ->get();

            foreach (
                $existingItems
                as $existingItem
            ) {
                $existingItem
                    ->addons()
                    ->delete();

                $existingItem->delete();
            }

            /*
            |--------------------------------------------------------------------------
            | Save updated order items
            |--------------------------------------------------------------------------
            */

            foreach (
                $preparedItems
                as $preparedItem
            ) {
                $orderItem = $lockedOrder
                    ->items()
                    ->create([
                        'menu_item_id' =>
                            $preparedItem[
                                'menu_item_id'
                            ],

                        'menu_item_variant_id' =>
                            $preparedItem[
                                'menu_item_variant_id'
                            ],

                        'item_name' =>
                            $preparedItem[
                                'item_name'
                            ],

                        'variant_name' =>
                            $preparedItem[
                                'variant_name'
                            ],

                        'unit_price' =>
                            $preparedItem[
                                'unit_price'
                            ],

                        'quantity' =>
                            $preparedItem[
                                'quantity'
                            ],

                        'addon_total' =>
                            $preparedItem[
                                'addon_total'
                            ],

                        'line_total' =>
                            $preparedItem[
                                'line_total'
                            ],

                        'status' =>
                            $newStatus,

                        'kitchen_note' =>
                            $preparedItem[
                                'kitchen_note'
                            ],
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Save updated item add-ons
                |--------------------------------------------------------------------------
                */

                foreach (
                    $preparedItem['addons']
                    as $addon
                ) {
                    $orderItem
                        ->addons()
                        ->create([
                            'menu_addon_id' =>
                                $addon['id'],

                            'addon_name' =>
                                $addon[
                                    'addon_name'
                                ],

                            'unit_price' =>
                                $addon[
                                    'unit_price'
                                ],

                            'quantity' =>
                                $preparedItem[
                                    'quantity'
                                ],

                            'total_price' =>
                                $addon[
                                    'total_price'
                                ],
                        ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Continue in Part 4
            |--------------------------------------------------------------------------
            */
                        /*
            |--------------------------------------------------------------------------
            | Handle customer change
            |--------------------------------------------------------------------------
            */

            if (
                $oldCustomerId &&
                (int) $oldCustomerId !==
                (int) $newCustomerId
            ) {
                $oldCustomer = Customer::query()
                    ->lockForUpdate()
                    ->find(
                        $oldCustomerId
                    );

                if ($oldCustomer) {
                    $oldCustomer->update([
                        'total_orders' =>
                            max(
                                0,
                                (int)
                                    $oldCustomer
                                        ->total_orders
                                - 1
                            ),
                    ]);
                }
            }

            if (
                $newCustomerId &&
                (int) $oldCustomerId !==
                (int) $newCustomerId
            ) {
                $newCustomer = Customer::query()
                    ->lockForUpdate()
                    ->find(
                        $newCustomerId
                    );

                if ($newCustomer) {
                    $newCustomer->update([
                        'total_orders' =>
                            (int)
                                $newCustomer
                                    ->total_orders
                            + 1,

                        'last_visit_at' =>
                            now(),
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Refresh order relationships
            |--------------------------------------------------------------------------
            */

            return $lockedOrder->fresh([
                'customer',
                'primaryTable',
                'tables',
                'items.addons',
                'payments.receiver',
                'creator',
            ]);
        }
    );
}

    public function updateStatus(
        Order $order,
        string $newStatus
    ): Order {
        if ($order->isFinalized()) {
            throw ValidationException::withMessages([
                'status' => [
                    'A completed or canceled order cannot be updated.',
                ],
            ]);
        }

        $updates = [
            'status' => $newStatus,
        ];

        if (
            $newStatus ===
            Order::STATUS_PREPARING
        ) {
            $updates[
                'preparing_at'
            ] = now();
        }

        if (
            $newStatus ===
            Order::STATUS_READY
        ) {
            $updates[
                'ready_at'
            ] = now();
        }

        if (
            $newStatus ===
            Order::STATUS_SERVED
        ) {
            $updates[
                'served_at'
            ] = now();
        }

        $order->update(
            $updates
        );

        $order->items()
            ->whereNot(
                'status',
                Order::STATUS_CANCELED
            )
            ->update([
                'status' =>
                    $newStatus,
            ]);

        return $order->fresh([
            'customer',
            'primaryTable',
            'tables',
            'items.addons',
            'creator',
        ]);
    }

    /**
/**
 * Cancel an active order.
 */
public function cancelOrder(
    Order $order,
    string $reason
): Order {
    return DB::transaction(
        function () use (
            $order,
            $reason
        ): Order {

            $lockedOrder =
                Order::query()
                    ->with([
                        'customer',
                        'tables',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $order->id
                    );


            /*
            |--------------------------------------------------------------------------
            | Prevent canceling finalized orders
            |--------------------------------------------------------------------------
            */

            if (
                $lockedOrder->isFinalized()
            ) {
                throw ValidationException::withMessages([
                    'order' => [
                        'This order is already completed or canceled.',
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Store original payment amount
            |--------------------------------------------------------------------------
            */

            $previousPaidAmount =
                round(
                    (float) $lockedOrder->paid_amount,
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | Reverse customer spending if already recorded
            |--------------------------------------------------------------------------
            */

            if (
                $lockedOrder->customer &&
                $lockedOrder->is_customer_spend_recorded &&
                $previousPaidAmount > 0
            ) {

                $customer =
                    Customer::query()
                        ->lockForUpdate()
                        ->find(
                            $lockedOrder->customer_id
                        );


                if ($customer) {

                    $customer->update([

                        'total_spent' =>
                            round(
                                max(
                                    0,
                                    (float)
                                        $customer->total_spent
                                        -
                                    $previousPaidAmount
                                ),
                                2
                            ),

                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Cancel Order & Reset Payment Summary
            |--------------------------------------------------------------------------
            */

            $lockedOrder->update([

                'status' =>
                    Order::STATUS_CANCELED,


                'canceled_at' =>
                    now(),


                'cancellation_reason' =>
                    trim($reason),



                /*
                |--------------------------------------------------------------------------
                | Payment Reset
                |--------------------------------------------------------------------------
                */

                'paid_amount' =>
                    0.00,


                'due_amount' =>
                    0.00,


                'payment_status' =>
                    Order::PAYMENT_DUE,


                'payment_method' =>
                    null,


                'payment_breakdown' =>
                    null,


                'payment_reference' =>
                    null,



                /*
                |--------------------------------------------------------------------------
                | Customer Spend Reset
                |--------------------------------------------------------------------------
                */

                'is_customer_spend_recorded' =>
                    false,


                'customer_spend_recorded_at' =>
                    null,

            ]);



            /*
            |--------------------------------------------------------------------------
            | Cancel all order items
            |--------------------------------------------------------------------------
            */

            $lockedOrder
                ->items()
                ->update([

                    'status' =>
                        Order::STATUS_CANCELED,

                ]);



            /*
            |--------------------------------------------------------------------------
            | Release Tables
            |--------------------------------------------------------------------------
            */

            $this->releaseOrderTables(
                $lockedOrder
            );



            /*
            |--------------------------------------------------------------------------
            | Return Fresh Order Data
            |--------------------------------------------------------------------------
            */

            return $lockedOrder->fresh([

                'customer',

                'primaryTable',

                'tables',

                'items.addons',

                'payments.receiver',

                'creator',

            ]);

        }
    );
}

    /**
     * Complete a served and fully paid order.
     */
    public function completeOrder(
        Order $order
    ): Order {
        return DB::transaction(
            function () use (
                $order
            ): Order {
                $lockedOrder =
                    Order::query()
                        ->with([
                            'customer',
                            'tables',
                        ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $order->id
                        );

                if (
                    !$lockedOrder
                        ->canBeCompleted()
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'The order must be served and fully paid before completion.',
                        ],
                    ]);
                }

                $lockedOrder->update([
                    'status' =>
                        Order::STATUS_COMPLETED,

                    'completed_at' =>
                        now(),
                ]);

                $lockedOrder
                    ->items()
                    ->update([
                        'status' =>
                            Order::STATUS_COMPLETED,
                    ]);

                if (
                    $lockedOrder->customer &&
                    !$lockedOrder
                        ->is_customer_spend_recorded
                ) {
                    $customer =
                        Customer::query()
                            ->lockForUpdate()
                            ->find(
                                $lockedOrder
                                    ->customer_id
                            );

                    if ($customer) {
                        $customer->update([
                            'total_spent' =>
                                round(
                                    (float)
                                        $customer
                                            ->total_spent
                                    +
                                    (float)
                                        $lockedOrder
                                            ->total_amount,
                                    2
                                ),

                            'last_visit_at' =>
                                now(),
                        ]);

                        $lockedOrder->update([
                            'is_customer_spend_recorded' =>
                                true,

                            'customer_spend_recorded_at' =>
                                now(),
                        ]);
                    }
                }

                $this->releaseOrderTables(
                    $lockedOrder
                );

                return $lockedOrder->fresh([
                    'customer',
                    'primaryTable',
                    'tables',
                    'items.addons',
                    'creator',
                ]);
            }
        );
    }

    /**
     * Find an existing customer or create a new one.
     */
    private function resolveCustomer(
        array $data
    ): ?Customer {
        $customerId =
            $data['customer_id']
            ?? null;

        if ($customerId) {
            return Customer::query()
                ->active()
                ->lockForUpdate()
                ->findOrFail(
                    (int) $customerId
                );
        }

        $name =
            $this->nullableString(
                $data[
                    'customer_name'
                ] ?? null
            );

        $phone =
            $this->nullableString(
                $data[
                    'customer_phone'
                ] ?? null
            );

        $email =
            $this->nullableString(
                $data[
                    'customer_email'
                ] ?? null
            );

        if (
            !$name &&
            !$phone &&
            !$email
        ) {
            return null;
        }

        $existingCustomer =
            Customer::query()
                ->where(
                    function (
                        Builder $query
                    ) use (
                        $phone,
                        $email
                    ): void {
                        if ($phone) {
                            $query->where(
                                'phone',
                                $phone
                            );
                        }

                        if (
                            $phone &&
                            $email
                        ) {
                            $query->orWhere(
                                'email',
                                $email
                            );
                        } elseif (
                            !$phone &&
                            $email
                        ) {
                            $query->where(
                                'email',
                                $email
                            );
                        }
                    }
                )
                ->lockForUpdate()
                ->first();

        if ($existingCustomer) {
            $existingCustomer->update([
                'name' =>
                    $name
                        ?? $existingCustomer
                            ->name,

                'phone' =>
                    $phone
                        ?? $existingCustomer
                            ->phone,

                'email' =>
                    $email
                        ?? $existingCustomer
                            ->email,
            ]);

            return $existingCustomer;
        }

        return Customer::query()
            ->create([
                'name' =>
                    $name
                        ?? 'Walk-in Customer',

                'phone' =>
                    $phone,

                'email' =>
                    $email,

                'last_visit_at' =>
                    null,

                'total_orders' =>
                    0,

                'total_spent' =>
                    0,

                'is_active' =>
                    true,

                'notes' =>
                    null,
            ]);
    }

    /**
     * Load, validate and calculate all order items.
     */
    private function prepareOrderItems(
        array $items
    ): Collection {
        return collect($items)
            ->values()
            ->map(
                function (
                    array $item,
                    int $index
                ): array {
                    $menuItem =
                        MenuItem::query()
                            ->with([
                                'variants' =>
                                    fn (
                                        $query
                                    ) =>
                                        $query
                                            ->available(),

                                'addOns' =>
                                    fn (
                                        $query
                                    ) =>
                                        $query
                                            ->available(),
                            ])
                            ->lockForUpdate()
                            ->findOrFail(
                                (int)
                                    $item[
                                        'menu_item_id'
                                    ]
                            );

                    if (
                        !$menuItem
                            ->is_available
                    ) {
                        throw ValidationException::withMessages([
                            "items.{$index}.menu_item_id" => [
                                "{$menuItem->menu_name} is currently unavailable.",
                            ],
                        ]);
                    }

                    $variant = null;

                    if (
                        !empty(
                            $item[
                                'menu_item_variant_id'
                            ]
                        )
                    ) {
                        $variant =
                            MenuItemVariant::query()
                                ->lockForUpdate()
                                ->where(
                                    'id',
                                    (int)
                                        $item[
                                            'menu_item_variant_id'
                                        ]
                                )
                                ->where(
                                    'menu_item_id',
                                    $menuItem->id
                                )
                                ->where(
                                    'is_available',
                                    true
                                )
                                ->first();

                        if (!$variant) {
                            throw ValidationException::withMessages([
                                "items.{$index}.menu_item_variant_id" => [
                                    'The selected variant does not belong to this menu item or is unavailable.',
                                ],
                            ]);
                        }
                    }

                    /*
                     * Variant price replaces base item price.
                     * If no variant is selected, use menu item price.
                     */
                    $unitPrice = round(
                        (float)
                            (
                                $variant?->price
                                ?? $menuItem->price
                            ),
                        2
                    );

                    $quantity = max(
                        1,
                        (int)
                            (
                                $item[
                                    'quantity'
                                ] ?? 1
                            )
                    );

                    $requestedAddonIds =
                        collect(
                            $item[
                                'addon_ids'
                            ] ?? []
                        )
                            ->map(
                                static fn (
                                    mixed $id
                                ): int =>
                                    (int) $id
                            )
                            ->unique()
                            ->values();

                    $addons =
                        $this->resolveItemAddons(
                            menuItem: $menuItem,
                            requestedAddonIds:
                                $requestedAddonIds,
                            itemIndex: $index,
                            quantity: $quantity
                        );

                    $addonTotalPerUnit =
                        round(
                            (float)
                                $addons->sum(
                                    'unit_price'
                                ),
                            2
                        );

                    $addonTotal = round(
                        $addonTotalPerUnit
                            * $quantity,
                        2
                    );

                    $lineTotal = round(
                        (
                            $unitPrice
                            +
                            $addonTotalPerUnit
                        )
                            * $quantity,
                        2
                    );

                    return [
                        'menu_item_id' =>
                            (int)
                                $menuItem->id,

                        'menu_item_variant_id' =>
                            $variant
                                ? (int)
                                    $variant->id
                                : null,

                        'item_name' =>
                            $menuItem
                                ->menu_name,

                        'variant_name' =>
                            $variant
                                ? $variant
                                    ->variant_name
                                : null,

                        'unit_price' =>
                            $unitPrice,

                        'quantity' =>
                            $quantity,

                        'addon_total' =>
                            $addonTotal,

                        'line_total' =>
                            $lineTotal,

                        'kitchen_note' =>
                            $this->nullableString(
                                $item[
                                    'kitchen_note'
                                ] ?? null
                            ),

                        'addons' =>
                            $addons->all(),
                    ];
                }
            );
    }

    /**
     * Validate add-ons against the selected menu item.
     */
    /**
 * Validate global add-ons.
 *
 * Any available add-on can be used with any menu item.
 */
private function resolveItemAddons(
    MenuItem $menuItem,
    Collection $requestedAddonIds,
    int $itemIndex,
    int $quantity
): Collection {
    if ($requestedAddonIds->isEmpty()) {
        return collect();
    }

    /*
    |--------------------------------------------------------------------------
    | Load all requested global add-ons
    |--------------------------------------------------------------------------
    */

    $availableAddons = AddOn::query()
        ->whereIn(
            'id',
            $requestedAddonIds
        )
        ->where(
            'is_available',
            true
        )
        ->lockForUpdate()
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Validate selected add-ons
    |--------------------------------------------------------------------------
    */

    if (
        $availableAddons->count()
        !==
        $requestedAddonIds->count()
    ) {
        throw ValidationException::withMessages([
            "items.{$itemIndex}.addon_ids" => [
                'One or more selected add-ons do not exist or are unavailable.',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare add-on price snapshot
    |--------------------------------------------------------------------------
    */

    return $availableAddons
        ->map(
            static function (
                AddOn $addon
            ) use (
                $quantity
            ): array {
                $unitPrice = round(
                    (float) $addon->price,
                    2
                );

                return [
                    'id' =>
                        (int) $addon->id,

                    'addon_name' =>
                        $addon->add_on_name,

                    'unit_price' =>
                        $unitPrice,

                    'total_price' =>
                        round(
                            $unitPrice
                            * $quantity,
                            2
                        ),
                ];
            }
        )
        ->values();
}

    /**
     * Load and validate merged tables.
     */
    private function loadMergedTables(
        Collection $mergedTableIds
    ): Collection {
        if (
            $mergedTableIds->isEmpty()
        ) {
            return collect();
        }

        $mergedTables =
            RestaurantTable::query()
                ->whereIn(
                    'id',
                    $mergedTableIds
                )
                ->lockForUpdate()
                ->get();

        if (
            $mergedTables->count()
            !==
            $mergedTableIds->count()
        ) {
            throw ValidationException::withMessages([
                'merged_table_ids' => [
                    'One or more selected merged tables no longer exist.',
                ],
            ]);
        }

        foreach (
            $mergedTables
            as $mergedTable
        ) {
            $this->ensureTableIsAvailable(
                table: $mergedTable,
                field: 'merged_table_ids'
            );
        }

        return $mergedTables;
    }

    /**
     * Ensure a table is currently usable.
     */
    private function ensureTableIsAvailable(
        RestaurantTable $table,
        string $field
    ): void {
        if (
            $table->current_status !==
            RestaurantTable::STATUS_AVAILABLE
        ) {
            throw ValidationException::withMessages([
                $field => [
                    "{$table->table_name} is not currently available.",
                ],
            ]);
        }

        if (
            $table->merged_with_id !==
            null
        ) {
            throw ValidationException::withMessages([
                $field => [
                    "{$table->table_name} already belongs to a merged table group.",
                ],
            ]);
        }

        $controlsAnotherGroup =
            RestaurantTable::query()
                ->where(
                    'merged_with_id',
                    $table->id
                )
                ->lockForUpdate()
                ->exists();

        if ($controlsAnotherGroup) {
            throw ValidationException::withMessages([
                $field => [
                    "{$table->table_name} already controls another merged table group.",
                ],
            ]);
        }

        $hasActiveOrder =
            $table->activeOrders()
                ->lockForUpdate()
                ->exists();

        if ($hasActiveOrder) {
            throw ValidationException::withMessages([
                $field => [
                    "{$table->table_name} already has an active order.",
                ],
            ]);
        }
    }

    /**
     * Release and split all tables assigned to an order.
     */
    private function releaseOrderTables(
        Order $order
    ): void {
        $order->loadMissing(
            'tables'
        );

        $tableIds =
            $order->tables
                ->pluck('id')
                ->map(
                    static fn (
                        mixed $id
                    ): int =>
                        (int) $id
                )
                ->unique()
                ->values();

        if (
            $tableIds->isEmpty() &&
            $order
                ->restaurant_table_id
        ) {
            $tableIds = collect([
                (int)
                    $order
                        ->restaurant_table_id,
            ]);
        }

        if (
            $tableIds->isEmpty()
        ) {
            return;
        }

        RestaurantTable::query()
            ->whereIn(
                'id',
                $tableIds
            )
            ->lockForUpdate()
            ->update([
                'status' =>
                    RestaurantTable::STATUS_AVAILABLE,

                'merged_with_id' =>
                    null,

                'reservation_start_at' =>
                    null,

                'reservation_end_at' =>
                    null,
            ]);
    }

    /**
     * Generate a unique order number.
     */
    private function generateOrderNumber(): string
    {
        do {
            $orderNumber =
                'ORD-'
                .now()->format(
                    'Ymd'
                )
                .'-'
                .strtoupper(
                    Str::random(6)
                );
        } while (
            Order::query()
                ->where(
                    'order_number',
                    $orderNumber
                )
                ->exists()
        );

        return $orderNumber;
    }

    /**
     * Return trimmed string or null.
     */
    private function nullableString(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }


    /**
     * Build timestamp values when an order status changes.
     */
    private function buildOrderStatusTimestamps(
        ?Order $order,
        string $status
    ): array {
        $timestamps = [
            'preparing_at' =>
                $order?->preparing_at,

            'ready_at' =>
                $order?->ready_at,

            'served_at' =>
                $order?->served_at,
        ];

        if (
            $status ===
            Order::STATUS_PREPARING &&
            !$timestamps['preparing_at']
        ) {
            $timestamps['preparing_at'] = now();
        }

        if (
            $status ===
            Order::STATUS_READY
        ) {
            if (!$timestamps['preparing_at']) {
                $timestamps['preparing_at'] = now();
            }

            if (!$timestamps['ready_at']) {
                $timestamps['ready_at'] = now();
            }
        }

        if (
            $status ===
            Order::STATUS_SERVED
        ) {
            if (!$timestamps['preparing_at']) {
                $timestamps['preparing_at'] = now();
            }

            if (!$timestamps['ready_at']) {
                $timestamps['ready_at'] = now();
            }

            if (!$timestamps['served_at']) {
                $timestamps['served_at'] = now();
            }
        }

        return $timestamps;
    }

    /**
 * Validate whether a table can be used while updating an order.
 *
 * Tables already assigned to the current order are allowed to remain
 * occupied. Newly selected tables must pass the normal availability check.
 */
private function ensureTableCanBeUsedForUpdate(
    RestaurantTable $table,
    string $field,
    Order $order,
    \Illuminate\Support\Collection $currentTableIds
): void {
    /*
    |--------------------------------------------------------------------------
    | Allow tables already assigned to this order
    |--------------------------------------------------------------------------
    |
    | Existing tables are normally marked as occupied. Since they belong to
    | this same order, they are still valid during an update.
    |
    */

    if (
        $currentTableIds->contains(
            (int) $table->id
        )
    ) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate newly selected table
    |--------------------------------------------------------------------------
    */

    $this->ensureTableIsAvailable(
        table: $table,
        field: $field
    );
}


}