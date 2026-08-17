<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderKitchenBatch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KitchenOrderService
{
    /*
    |--------------------------------------------------------------------------
    | Kitchen Relations
    |--------------------------------------------------------------------------
    |
    | Order-level kitchen fields remain compatibility mirrors.
    | Latest Kitchen Batch is the authoritative current kitchen cycle.
    |
    */

    private const KITCHEN_RELATIONS = [
        'chef:id,name,username,role_id',
        'primaryTable',
        'tables',

        'items.menuItem',
        'items.addons',

        'latestKitchenBatch.chef:id,name,username,role_id',
        'latestKitchenBatch.items.menuItem',
        'latestKitchenBatch.items.addons',
        'latestKitchenBatch.recipeConsumption',

        'recipeConsumption',
    ];

    /*
    |--------------------------------------------------------------------------
    | Kitchen Order List
    |--------------------------------------------------------------------------
    */

    public function getKitchenOrders(
        array $filters = []
    ): LengthAwarePaginator {
        $perPage = max(
            1,
            min(
                (int) (
                    $filters['per_page']
                    ?? 20
                ),
                100
            )
        );

        $search = trim(
            (string) (
                $filters['search']
                ?? ''
            )
        );

        $status = trim(
            (string) (
                $filters['status']
                ?? ''
            )
        );

        $assignment = trim(
            (string) (
                $filters['assignment']
                ?? ''
            )
        );

        $chefId =
            $filters['chef_id']
            ?? null;

        return Order::query()
            ->with(
                self::KITCHEN_RELATIONS
            )

            /*
            |--------------------------------------------------------------------------
            | Kitchen-visible Orders
            |--------------------------------------------------------------------------
            */

            ->whereIn(
                'status',
                [
                    Order::STATUS_PENDING,
                    Order::STATUS_PREPARING,
                    Order::STATUS_READY,
                ]
            )

            /*
            |--------------------------------------------------------------------------
            | Active Latest Kitchen Batch Required
            |--------------------------------------------------------------------------
            |
            | Historical served batches must never come back into the queue.
            |
            */

            ->whereHas(
                'latestKitchenBatch',
                function (
                    Builder $batchQuery
                ): void {
                    $batchQuery
                        ->whereIn(
                            'status',
                            OrderKitchenBatch::
                                activeStatuses()
                        );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Status Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                $status !== '',
                function (
                    Builder $query
                ) use (
                    $status
                ): void {
                    if (
                        ! in_array(
                            $status,
                            OrderKitchenBatch::
                                activeStatuses(),
                            true
                        )
                    ) {
                        return;
                    }

                    $query->whereHas(
                        'latestKitchenBatch',
                        function (
                            Builder $batchQuery
                        ) use (
                            $status
                        ): void {
                            $batchQuery->where(
                                'status',
                                $status
                            );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Assignment Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                $assignment !== '',
                function (
                    Builder $query
                ) use (
                    $assignment
                ): void {
                    if (
                        $assignment ===
                        'assigned'
                    ) {
                        $query->whereHas(
                            'latestKitchenBatch',
                            function (
                                Builder $batchQuery
                            ): void {
                                $batchQuery
                                    ->whereNotNull(
                                        'chef_id'
                                    );
                            }
                        );

                        return;
                    }

                    if (
                        $assignment ===
                        'unassigned'
                    ) {
                        $query->whereHas(
                            'latestKitchenBatch',
                            function (
                                Builder $batchQuery
                            ): void {
                                $batchQuery
                                    ->whereNull(
                                        'chef_id'
                                    );
                            }
                        );
                    }
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Chef Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                filled(
                    $chefId
                ),
                function (
                    Builder $query
                ) use (
                    $chefId
                ): void {
                    $query->whereHas(
                        'latestKitchenBatch',
                        function (
                            Builder $batchQuery
                        ) use (
                            $chefId
                        ): void {
                            $batchQuery->where(
                                'chef_id',
                                (int) $chefId
                            );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            ->when(
                $search !== '',
                function (
                    Builder $query
                ) use (
                    $search
                ): void {
                    $query->where(
                        function (
                            Builder $builder
                        ) use (
                            $search
                        ): void {
                            $builder
                                ->where(
                                    'order_number',
                                    'like',
                                    "%{$search}%"
                                )

                                ->orWhere(
                                    'customer_name',
                                    'like',
                                    "%{$search}%"
                                )

                                ->orWhere(
                                    'customer_phone',
                                    'like',
                                    "%{$search}%"
                                )

                                /*
                                |--------------------------------------------------------------------------
                                | Table Search
                                |--------------------------------------------------------------------------
                                */

                                ->orWhereHas(
                                    'primaryTable',
                                    function (
                                        Builder $tableQuery
                                    ) use (
                                        $search
                                    ): void {
                                        $tableQuery
                                            ->where(
                                                'table_name',
                                                'like',
                                                "%{$search}%"
                                            )

                                            ->orWhere(
                                                'section',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )

                                /*
                                |--------------------------------------------------------------------------
                                | Current Batch Chef Search
                                |--------------------------------------------------------------------------
                                */

                                ->orWhereHas(
                                    'latestKitchenBatch.chef',
                                    function (
                                        Builder $chefQuery
                                    ) use (
                                        $search
                                    ): void {
                                        $chefQuery
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            )

                                            ->orWhere(
                                                'username',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )

                                /*
                                |--------------------------------------------------------------------------
                                | Current Batch Item Search
                                |--------------------------------------------------------------------------
                                */

                                ->orWhereHas(
                                    'latestKitchenBatch.items',
                                    function (
                                        Builder $itemQuery
                                    ) use (
                                        $search
                                    ): void {
                                        $itemQuery
                                            ->where(
                                                'item_name',
                                                'like',
                                                "%{$search}%"
                                            )

                                            ->orWhere(
                                                'variant_name',
                                                'like',
                                                "%{$search}%"
                                            )

                                            ->orWhereHas(
                                                'menuItem',
                                                function (
                                                    Builder $menuItemQuery
                                                ) use (
                                                    $search
                                                ): void {
                                                    $menuItemQuery
                                                        ->where(
                                                            'ingredients',
                                                            'like',
                                                            "%{$search}%"
                                                        );
                                                }
                                            );
                                    }
                                );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Kitchen Priority
            |--------------------------------------------------------------------------
            |
            | Order-level status and chef_id remain compatibility mirrors of the
            | latest active batch.
            |
            */

            ->orderByRaw(
                '
                CASE
                    WHEN status = ? AND chef_id IS NULL
                        THEN 1

                    WHEN status = ? AND chef_id IS NOT NULL
                        THEN 2

                    WHEN status = ?
                        THEN 3

                    WHEN status = ?
                        THEN 4

                    ELSE 5
                END
                ',
                [
                    Order::STATUS_PENDING,
                    Order::STATUS_PENDING,
                    Order::STATUS_PREPARING,
                    Order::STATUS_READY,
                ]
            )

            ->orderBy(
                'created_at',
                'asc'
            )

            ->paginate(
                $perPage
            )

            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Show Kitchen Order
    |--------------------------------------------------------------------------
    */

    public function getKitchenOrder(
        Order $order
    ): Order {
        return $order->load(
            self::KITCHEN_RELATIONS
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accept Kitchen Order
    |--------------------------------------------------------------------------
    */

    public function acceptOrder(
        Order $order,
        User $user
    ): Order {
        return DB::transaction(
            function () use (
                $order,
                $user
            ): Order {
                /*
                |--------------------------------------------------------------------------
                | Chef Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureUserIsChef(
                    $user
                );

                /*
                |--------------------------------------------------------------------------
                | Lock Parent Order
                |--------------------------------------------------------------------------
                */

                $lockedOrder =
                    $this->lockKitchenOrder(
                        $order
                    );

                $this->ensureOrderIsActive(
                    $lockedOrder
                );

                /*
                |--------------------------------------------------------------------------
                | Lock Current Kitchen Batch
                |--------------------------------------------------------------------------
                */

                $lockedBatch =
                    $this->lockCurrentKitchenBatch(
                        $lockedOrder
                    );

                /*
                |--------------------------------------------------------------------------
                | Pending Batch Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedBatch->status !==
                    OrderKitchenBatch::
                        STATUS_PENDING
                ) {
                    throw ValidationException::
                        withMessages([
                            'kitchen_batch' => [
                                'Only a pending kitchen batch can be accepted.',
                            ],
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Existing Chef Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedBatch->chef_id !==
                    null
                ) {
                    if (
                        (int)
                            $lockedBatch
                                ->chef_id
                        ===
                        (int) $user->id
                    ) {
                        throw ValidationException::
                            withMessages([
                                'kitchen_batch' => [
                                    'You have already accepted this kitchen batch.',
                                ],
                            ]);
                    }

                    throw ValidationException::
                        withMessages([
                            'kitchen_batch' => [
                                'This kitchen batch has already been accepted by another chef.',
                            ],
                        ]);
                }

                $acceptedAt =
                    $lockedBatch
                        ->sent_to_kitchen_at
                    ?? now();

                /*
                |--------------------------------------------------------------------------
                | Update Authoritative Batch
                |--------------------------------------------------------------------------
                */

                $lockedBatch->update([
                    'chef_id' =>
                        (int) $user->id,

                    'sent_to_kitchen_at' =>
                        $acceptedAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Only Current Batch Items Remain Pending
                |--------------------------------------------------------------------------
                */

                $lockedBatch
                    ->items()
                    ->update([
                        'status' =>
                            OrderKitchenBatch::
                                STATUS_PENDING,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Compatibility Mirror On Order
                |--------------------------------------------------------------------------
                */

                $lockedOrder->update([
                    'chef_id' =>
                        (int) $user->id,

                    'sent_to_kitchen_at' =>
                        $acceptedAt,
                ]);

                return $this
                    ->freshKitchenOrder(
                        $lockedOrder
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Start Preparing
    |--------------------------------------------------------------------------
    */

    public function startPreparing(
        Order $order,
        User $user
    ): Order {
        return DB::transaction(
            function () use (
                $order,
                $user
            ): Order {
                /*
                |--------------------------------------------------------------------------
                | Chef Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureUserIsChef(
                    $user
                );

                /*
                |--------------------------------------------------------------------------
                | Lock Order
                |--------------------------------------------------------------------------
                */

                $lockedOrder =
                    $this->lockKitchenOrder(
                        $order
                    );

                $this->ensureOrderIsActive(
                    $lockedOrder
                );

                /*
                |--------------------------------------------------------------------------
                | Lock Current Batch
                |--------------------------------------------------------------------------
                */

                $lockedBatch =
                    $this->lockCurrentKitchenBatch(
                        $lockedOrder
                    );

                /*
                |--------------------------------------------------------------------------
                | Assigned Chef Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureAssignedChef(
                    batch:
                        $lockedBatch,

                    user:
                        $user
                );

                /*
                |--------------------------------------------------------------------------
                | Pending Batch Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedBatch->status !==
                    OrderKitchenBatch::
                        STATUS_PENDING
                ) {
                    throw ValidationException::
                        withMessages([
                            'kitchen_batch' => [
                                'Only an accepted pending kitchen batch can start preparation.',
                            ],
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Acceptance Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedBatch
                        ->sent_to_kitchen_at
                    ===
                    null
                ) {
                    throw ValidationException::
                        withMessages([
                            'kitchen_batch' => [
                                'The kitchen batch must be accepted before preparation starts.',
                            ],
                        ]);
                }

                $preparingAt =
                    $lockedBatch
                        ->preparing_at
                    ?? now();

                /*
                |--------------------------------------------------------------------------
                | Update Authoritative Batch
                |--------------------------------------------------------------------------
                */

                $lockedBatch->update([
                    'status' =>
                        OrderKitchenBatch::
                            STATUS_PREPARING,

                    'preparing_at' =>
                        $preparingAt,

                    'ready_at' =>
                        null,

                    'served_at' =>
                        null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Current Batch Items Only
                |--------------------------------------------------------------------------
                */

                $lockedBatch
                    ->items()
                    ->update([
                        'status' =>
                            OrderKitchenBatch::
                                STATUS_PREPARING,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Compatibility Mirror On Order
                |--------------------------------------------------------------------------
                */

                $lockedOrder->update([
                    'status' =>
                        Order::STATUS_PREPARING,

                    'chef_id' =>
                        (int)
                            $lockedBatch
                                ->chef_id,

                    'sent_to_kitchen_at' =>
                        $lockedBatch
                            ->sent_to_kitchen_at,

                    'preparing_at' =>
                        $preparingAt,

                    'ready_at' =>
                        null,

                    'served_at' =>
                        null,
                ]);

                return $this
                    ->freshKitchenOrder(
                        $lockedOrder
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Ready
    |--------------------------------------------------------------------------
    */

    public function markReady(
        Order $order,
        User $user
    ): Order {
        return DB::transaction(
            function () use (
                $order,
                $user
            ): Order {
                /*
                |--------------------------------------------------------------------------
                | Chef Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureUserIsChef(
                    $user
                );

                /*
                |--------------------------------------------------------------------------
                | Lock Order
                |--------------------------------------------------------------------------
                */

                $lockedOrder =
                    $this->lockKitchenOrder(
                        $order
                    );

                $this->ensureOrderIsActive(
                    $lockedOrder
                );

                /*
                |--------------------------------------------------------------------------
                | Lock Current Batch
                |--------------------------------------------------------------------------
                */

                $lockedBatch =
                    $this->lockCurrentKitchenBatch(
                        $lockedOrder
                    );

                /*
                |--------------------------------------------------------------------------
                | Assigned Chef Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureAssignedChef(
                    batch:
                        $lockedBatch,

                    user:
                        $user
                );

                /*
                |--------------------------------------------------------------------------
                | Preparing Batch Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedBatch->status !==
                    OrderKitchenBatch::
                        STATUS_PREPARING
                ) {
                    throw ValidationException::
                        withMessages([
                            'kitchen_batch' => [
                                'Only a preparing kitchen batch can be marked as ready.',
                            ],
                        ]);
                }

                if (
                    $lockedBatch
                        ->preparing_at
                    ===
                    null
                ) {
                    throw ValidationException::
                        withMessages([
                            'kitchen_batch' => [
                                'Preparation must be started before the kitchen batch can be marked ready.',
                            ],
                        ]);
                }

                $readyAt =
                    $lockedBatch
                        ->ready_at
                    ?? now();

                /*
                |--------------------------------------------------------------------------
                | Update Authoritative Batch
                |--------------------------------------------------------------------------
                */

                $lockedBatch->update([
                    'status' =>
                        OrderKitchenBatch::
                            STATUS_READY,

                    'ready_at' =>
                        $readyAt,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Current Batch Items Only
                |--------------------------------------------------------------------------
                */

                $lockedBatch
                    ->items()
                    ->update([
                        'status' =>
                            OrderKitchenBatch::
                                STATUS_READY,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Compatibility Mirror On Order
                |--------------------------------------------------------------------------
                */

                $lockedOrder->update([
                    'status' =>
                        Order::STATUS_READY,

                    'chef_id' =>
                        (int)
                            $lockedBatch
                                ->chef_id,

                    'ready_at' =>
                        $readyAt,
                ]);

                return $this
                    ->freshKitchenOrder(
                        $lockedOrder
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lock Kitchen Order
    |--------------------------------------------------------------------------
    */

    private function lockKitchenOrder(
        Order $order
    ): Order {
        return Order::query()
            ->with(
                self::KITCHEN_RELATIONS
            )
            ->lockForUpdate()
            ->findOrFail(
                $order->id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Lock Current Kitchen Batch
    |--------------------------------------------------------------------------
    |
    | Only latest active batch can receive kitchen actions.
    |
    */

    private function lockCurrentKitchenBatch(
        Order $order
    ): OrderKitchenBatch {
        $batch =
            OrderKitchenBatch::query()
                ->where(
                    'order_id',
                    $order->id
                )

                ->whereIn(
                    'status',
                    OrderKitchenBatch::
                        activeStatuses()
                )

                ->orderByDesc(
                    'batch_no'
                )

                ->orderByDesc(
                    'id'
                )

                ->lockForUpdate()

                ->first();

        if (
            ! $batch
        ) {
            throw ValidationException::
                withMessages([
                    'kitchen_batch' => [
                        'No active kitchen batch is available for this order.',
                    ],
                ]);
        }

        return $batch;
    }

    /*
    |--------------------------------------------------------------------------
    | Chef Role Protection
    |--------------------------------------------------------------------------
    */

    private function ensureUserIsChef(
        User $user
    ): void {
        $user->loadMissing(
            'role'
        );

        $roleName =
            strtolower(
                trim(
                    (string) (
                        $user->role?->name
                        ?? ''
                    )
                )
            );

        if (
            $roleName !==
            'chef'
        ) {
            throw ValidationException::
                withMessages([
                    'user' => [
                        'Only a chef can update kitchen order status.',
                    ],
                ]);
        }

        if (
            ! $user->is_active
        ) {
            throw ValidationException::
                withMessages([
                    'user' => [
                        'Your user account is inactive.',
                    ],
                ]);
        }

        if (
            $user->blocked_at !==
            null
        ) {
            throw ValidationException::
                withMessages([
                    'user' => [
                        'Your user account is blocked.',
                    ],
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Active Order Protection
    |--------------------------------------------------------------------------
    */

    private function ensureOrderIsActive(
        Order $order
    ): void {
        if (
            $order->isFinalized()
        ) {
            throw ValidationException::
                withMessages([
                    'order' => [
                        'A completed or canceled order cannot be updated by the kitchen.',
                    ],
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Served Without Extension
        |--------------------------------------------------------------------------
        |
        | A served order is not currently active in the kitchen.
        | OrderService will later create a new pending batch when the customer
        | extends the same bill.
        |
        */

        if (
            $order->status ===
            Order::STATUS_SERVED
        ) {
            throw ValidationException::
                withMessages([
                    'order' => [
                        'This order has no active kitchen cycle. Add a new order extension before sending more items to the kitchen.',
                    ],
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned Chef Protection
    |--------------------------------------------------------------------------
    */

    private function ensureAssignedChef(
        OrderKitchenBatch $batch,
        User $user
    ): void {
        if (
            $batch->chef_id ===
            null
        ) {
            throw ValidationException::
                withMessages([
                    'kitchen_batch' => [
                        'This kitchen batch has not been accepted by a chef yet.',
                    ],
                ]);
        }

        if (
            (int) $batch->chef_id !==
            (int) $user->id
        ) {
            throw ValidationException::
                withMessages([
                    'kitchen_batch' => [
                        'Only the assigned chef can update this kitchen batch.',
                    ],
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fresh Kitchen Order
    |--------------------------------------------------------------------------
    */

    private function freshKitchenOrder(
        Order $order
    ): Order {
        return $order->fresh(
            self::KITCHEN_RELATIONS
        );
    }
}