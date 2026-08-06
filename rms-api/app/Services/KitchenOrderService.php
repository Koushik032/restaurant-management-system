<?php

namespace App\Services;

use App\Models\Order;
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
    */

    private const KITCHEN_RELATIONS = [
        'chef:id,name,username,role_id',
        'primaryTable',
        'tables',
        'items.menuItem',
        'items.addons',
    ];

    /*
    |--------------------------------------------------------------------------
    | Kitchen Order List
    |--------------------------------------------------------------------------
    */

    /**
     * Return active kitchen orders with filters and pagination.
     */
    public function getKitchenOrders(
        array $filters = []
    ): LengthAwarePaginator {
        $perPage = max(
            1,
            min(
                (int) ($filters['per_page'] ?? 20),
                100
            )
        );

        $search = trim(
            (string) ($filters['search'] ?? '')
        );

        $status = trim(
            (string) ($filters['status'] ?? '')
        );

        $assignment = trim(
            (string) ($filters['assignment'] ?? '')
        );

        $chefId = $filters['chef_id'] ?? null;

        return Order::query()
            ->with(self::KITCHEN_RELATIONS)

            /*
            |--------------------------------------------------------------------------
            | Kitchen-visible Statuses
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
            | Status Filter
            |--------------------------------------------------------------------------
            */

            ->when(
                $status !== '',
                function (
                    Builder $query
                ) use ($status): void {
                    if (
                        in_array(
                            $status,
                            [
                                Order::STATUS_PENDING,
                                Order::STATUS_PREPARING,
                                Order::STATUS_READY,
                            ],
                            true
                        )
                    ) {
                        $query->where(
                            'status',
                            $status
                        );
                    }
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
                ) use ($assignment): void {
                    if ($assignment === 'assigned') {
                        $query->whereNotNull(
                            'chef_id'
                        );

                        return;
                    }

                    if ($assignment === 'unassigned') {
                        $query->whereNull(
                            'chef_id'
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
                filled($chefId),
                function (
                    Builder $query
                ) use ($chefId): void {
                    $query->where(
                        'chef_id',
                        (int) $chefId
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
                ) use ($search): void {
                    $query->where(
                        function (
                            Builder $builder
                        ) use ($search): void {
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
                                | Search Table
                                |--------------------------------------------------------------------------
                                */

                                ->orWhereHas(
                                    'primaryTable',
                                    function (
                                        Builder $tableQuery
                                    ) use ($search): void {
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
                                | Search Assigned Chef
                                |--------------------------------------------------------------------------
                                */

                                ->orWhereHas(
                                    'chef',
                                    function (
                                        Builder $chefQuery
                                    ) use ($search): void {
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
                                | Search Menu Item
                                |--------------------------------------------------------------------------
                                */

                                ->orWhereHas(
                                    'items',
                                    function (
                                        Builder $itemQuery
                                    ) use ($search): void {
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
                                                ) use ($search): void {
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
            | Kitchen Priority Order
            |--------------------------------------------------------------------------
            |
            | 1. Unassigned pending orders
            | 2. Accepted pending orders
            | 3. Preparing orders
            | 4. Ready orders
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

            /*
            |--------------------------------------------------------------------------
            | Oldest Orders First
            |--------------------------------------------------------------------------
            */

            ->orderBy(
                'created_at',
                'asc'
            )

            ->paginate($perPage)

            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Show Kitchen Order
    |--------------------------------------------------------------------------
    */

    /**
     * Return a single kitchen order with required relationships.
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

    /**
     * Assign an unassigned pending order to the authenticated chef.
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
                | Chef Role Protection
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

                /*
                |--------------------------------------------------------------------------
                | Finalized Order Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureOrderIsActive(
                    $lockedOrder
                );

                /*
                |--------------------------------------------------------------------------
                | Pending Status Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedOrder->status !==
                    Order::STATUS_PENDING
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'Only a pending order can be accepted.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Already Assigned Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedOrder->chef_id !== null
                ) {
                    if (
                        (int) $lockedOrder->chef_id ===
                        (int) $user->id
                    ) {
                        throw ValidationException::withMessages([
                            'order' => [
                                'You have already accepted this order.',
                            ],
                        ]);
                    }

                    throw ValidationException::withMessages([
                        'order' => [
                            'This order has already been accepted by another chef.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Assign Chef
                |--------------------------------------------------------------------------
                */

                $lockedOrder->update([
                    'chef_id' =>
                        (int) $user->id,

                    'sent_to_kitchen_at' =>
                        $lockedOrder
                            ->sent_to_kitchen_at
                        ?? now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Keep Items Pending After Acceptance
                |--------------------------------------------------------------------------
                */

                $lockedOrder
                    ->items()
                    ->update([
                        'status' =>
                            Order::STATUS_PENDING,
                    ]);

                return $this->freshKitchenOrder(
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

    /**
     * Move an accepted pending order to preparing.
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
                | Chef Role Protection
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

                /*
                |--------------------------------------------------------------------------
                | Active Order Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureOrderIsActive(
                    $lockedOrder
                );

                /*
                |--------------------------------------------------------------------------
                | Assigned Chef Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureAssignedChef(
                    order: $lockedOrder,
                    user: $user
                );

                /*
                |--------------------------------------------------------------------------
                | Correct Status Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedOrder->status !==
                    Order::STATUS_PENDING
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'Only an accepted pending order can start preparation.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Acceptance Timestamp Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedOrder->sent_to_kitchen_at ===
                    null
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'The order must be accepted before preparation starts.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Start Preparing
                |--------------------------------------------------------------------------
                */

                $lockedOrder->update([
                    'status' =>
                        Order::STATUS_PREPARING,

                    'preparing_at' =>
                        $lockedOrder
                            ->preparing_at
                        ?? now(),

                    /*
                    |--------------------------------------------------------------------------
                    | Reset Ready Timestamp
                    |--------------------------------------------------------------------------
                    */

                    'ready_at' => null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Synchronize Order Item Status
                |--------------------------------------------------------------------------
                */

                $lockedOrder
                    ->items()
                    ->update([
                        'status' =>
                            Order::STATUS_PREPARING,
                    ]);

                return $this->freshKitchenOrder(
                    $lockedOrder
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Kitchen Order Ready
    |--------------------------------------------------------------------------
    */

    /**
     * Move a preparing order to ready.
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
                | Chef Role Protection
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

                /*
                |--------------------------------------------------------------------------
                | Active Order Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureOrderIsActive(
                    $lockedOrder
                );

                /*
                |--------------------------------------------------------------------------
                | Assigned Chef Protection
                |--------------------------------------------------------------------------
                */

                $this->ensureAssignedChef(
                    order: $lockedOrder,
                    user: $user
                );

                /*
                |--------------------------------------------------------------------------
                | Correct Status Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedOrder->status !==
                    Order::STATUS_PREPARING
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'Only a preparing order can be marked as ready.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Preparing Timestamp Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedOrder->preparing_at ===
                    null
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            'Preparation must be started before the order can be marked ready.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Mark Ready
                |--------------------------------------------------------------------------
                */

                $lockedOrder->update([
                    'status' =>
                        Order::STATUS_READY,

                    'ready_at' =>
                        $lockedOrder
                            ->ready_at
                        ?? now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Synchronize Order Item Status
                |--------------------------------------------------------------------------
                */

                $lockedOrder
                    ->items()
                    ->update([
                        'status' =>
                            Order::STATUS_READY,
                    ]);

                return $this->freshKitchenOrder(
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

    /**
     * Lock the order to avoid concurrent kitchen updates.
     */
    private function lockKitchenOrder(
        Order $order
    ): Order {
        return Order::query()
            ->with(self::KITCHEN_RELATIONS)
            ->lockForUpdate()
            ->findOrFail(
                $order->id
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Chef Role Protection
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure only Chef-role users can perform kitchen actions.
     */
    private function ensureUserIsChef(
        User $user
    ): void {
        $user->loadMissing(
            'role'
        );

        $roleName = strtolower(
            trim(
                (string) (
                    $user->role?->name
                    ?? ''
                )
            )
        );

        if ($roleName !== 'chef') {
            throw ValidationException::withMessages([
                'user' => [
                    'Only a chef can update kitchen order status.',
                ],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'user' => [
                    'Your user account is inactive.',
                ],
            ]);
        }

        if ($user->blocked_at !== null) {
            throw ValidationException::withMessages([
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

    /**
     * Ensure finalized orders cannot be updated by the kitchen.
     */
    private function ensureOrderIsActive(
        Order $order
    ): void {
        if ($order->isFinalized()) {
            throw ValidationException::withMessages([
                'order' => [
                    'A completed or canceled order cannot be updated by the kitchen.',
                ],
            ]);
        }

        if (
            in_array(
                $order->status,
                [
                    Order::STATUS_SERVED,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELED,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'order' => [
                    'This order is no longer active in the kitchen.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Assigned Chef Protection
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure only the assigned chef can update kitchen progress.
     */
    private function ensureAssignedChef(
        Order $order,
        User $user
    ): void {
        if ($order->chef_id === null) {
            throw ValidationException::withMessages([
                'order' => [
                    'This order has not been accepted by a chef yet.',
                ],
            ]);
        }

        if (
            (int) $order->chef_id !==
            (int) $user->id
        ) {
            throw ValidationException::withMessages([
                'order' => [
                    'Only the assigned chef can update this order.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Fresh Kitchen Order
    |--------------------------------------------------------------------------
    */

    /**
     * Reload the updated kitchen order and all required relations.
     */
    private function freshKitchenOrder(
        Order $order
    ): Order {
        return $order->fresh(
            self::KITCHEN_RELATIONS
        );
    }
}