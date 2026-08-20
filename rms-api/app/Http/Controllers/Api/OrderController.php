<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelOrderRequest;
use App\Http\Requests\Api\OrderIndexRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Models\RestaurantTable;
use App\Models\MenuItem;
use App\Models\AddOn;
use App\Models\MenuCategory;
use App\Http\Requests\Api\UpdateOrderRequest;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function createOptions(): JsonResponse
{
    /*
    |--------------------------------------------------------------------------
    | Available Tables
    |--------------------------------------------------------------------------
    */

    $tables = RestaurantTable::query()
        ->where(
            'status',
            'available'
        )
        ->orderBy(
            'table_name'
        )
        ->get([
            'id',
            'table_name',
            'capacity',
            'section',
            'status',
        ]);


    /*
    |--------------------------------------------------------------------------
    | Available Menu Categories
    |--------------------------------------------------------------------------
    */

    $categories = MenuCategory::query()
        ->available()
        ->ordered()
        ->get([
            'id',
            'category_name',
            'description',
            'display_order',
            'is_available',
        ]);


    /*
    |--------------------------------------------------------------------------
    | Menu Items With Variants
    |--------------------------------------------------------------------------
    */

    $menuItems = MenuItem::query()
        ->available()
        ->with([
            'category',
            'variants',
            'addOns',
        ])
        ->orderBy(
            'menu_name'
        )
        ->get();


    /*
    |--------------------------------------------------------------------------
    | All Addons
    |--------------------------------------------------------------------------
    */

    $addons = AddOn::query()
        ->available()
        ->orderBy(
            'add_on_name'
        )
        ->get([
            'id',
            'add_on_name',
            'price',
        ]);


    /*
    |--------------------------------------------------------------------------
    | Logged In User
    |--------------------------------------------------------------------------
    */

    $user = auth()->user();


    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return response()->json([

        'success' => true,

        'data' => [

            /*
            |--------------------------------------------------------------------------
            | Tables
            |--------------------------------------------------------------------------
            */

            'tables' => $tables,


            /*
            |--------------------------------------------------------------------------
            | Categories
            |--------------------------------------------------------------------------
            */

            'categories' => $categories,


            /*
            |--------------------------------------------------------------------------
            | Menu Items
            |--------------------------------------------------------------------------
            */

            'menu_items' => $menuItems,


            /*
            |--------------------------------------------------------------------------
            | Addons
            |--------------------------------------------------------------------------
            */

            'addons' => $addons,


            /*
            |--------------------------------------------------------------------------
            | Waiter
            |--------------------------------------------------------------------------
            */

            'waiter' => [

                'id' => $user?->id,

                'name' => $user?->name,

            ],

        ],

    ]);
}

    public function index(
        OrderIndexRequest $request
    ): JsonResponse {
        $filters = $request->validated();

        $baseQuery = Order::query()
            ->with([
                'customer',
                'primaryTable',
                'tables',
            ]);

        $this->applyFilters(
            query: $baseQuery,
            filters: $filters
        );

        $orders = $baseQuery
            ->orderBy(
                'created_at',
                $filters['sort_direction'] ?? 'desc'
            )
            ->paginate(
                (int) ($filters['per_page'] ?? 10)
            )
            ->withQueryString();

        $summaryQuery = Order::query();

        /*
         * Summary cards-এ status filter apply করছি না,
         * যাতে সব status-এর count একসঙ্গে দেখা যায়।
         */
        $summaryFilters = $filters;

        unset(
            $summaryFilters['status'],
            $summaryFilters['payment_status'],
            $summaryFilters['payment_method'],
            $summaryFilters['per_page'],
            $summaryFilters['page'],
            $summaryFilters['sort_direction']
        );

        $this->applyFilters(
            query: $summaryQuery,
            filters: $summaryFilters
        );

        $summary = [
            'total_orders' =>
                (clone $summaryQuery)->count(),

            'pending_orders' =>
                (clone $summaryQuery)
                    ->where(
                        'status',
                        Order::STATUS_PENDING
                    )
                    ->count(),

            'kitchen_active' =>
                (clone $summaryQuery)
                    ->whereIn(
                        'status',
                        [
                            Order::STATUS_PREPARING,
                            Order::STATUS_READY,
                        ]
                    )
                    ->count(),

            'served_orders' =>
                (clone $summaryQuery)
                    ->where(
                        'status',
                        Order::STATUS_SERVED
                    )
                    ->count(),

            'completed_orders' =>
                (clone $summaryQuery)
                    ->where(
                        'status',
                        Order::STATUS_COMPLETED
                    )
                    ->count(),

            'canceled_orders' =>
                (clone $summaryQuery)
                    ->where(
                        'status',
                        Order::STATUS_CANCELED
                    )
                    ->count(),

            'total_sales' =>
                (float) (clone $summaryQuery)
                    ->where(
                        'status',
                        Order::STATUS_COMPLETED
                    )
                    ->sum('total_amount'),

            'paid_amount' =>
                (float) (clone $summaryQuery)
                    ->sum('paid_amount'),

            'due_amount' =>
                (float) (clone $summaryQuery)
                    ->whereNotIn(
                        'status',
                        [
                            Order::STATUS_CANCELED,
                        ]
                    )
                    ->sum('due_amount'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Orders loaded successfully.',

            'data' => OrderResource::collection(
                $orders->getCollection()
            ),

            'summary' => [
                ...$summary,

                'total_sales_formatted' =>
                    '৳ '.number_format(
                        $summary['total_sales'],
                        2
                    ),

                'paid_amount_formatted' =>
                    '৳ '.number_format(
                        $summary['paid_amount'],
                        2
                    ),

                'due_amount_formatted' =>
                    '৳ '.number_format(
                        $summary['due_amount'],
                        2
                    ),
            ],

            'meta' => [
                'current_page' =>
                    $orders->currentPage(),

                'last_page' =>
                    $orders->lastPage(),

                'per_page' =>
                    $orders->perPage(),

                'total' =>
                    $orders->total(),

                'from' =>
                    $orders->firstItem(),

                'to' =>
                    $orders->lastItem(),
            ],

            'filters' => [
                'statuses' => [
                    [
                        'value' => Order::STATUS_PENDING,
                        'label' => 'Pending',
                    ],
                    [
                        'value' => Order::STATUS_PREPARING,
                        'label' => 'Preparing',
                    ],
                    [
                        'value' => Order::STATUS_READY,
                        'label' => 'Ready',
                    ],
                    [
                        'value' => Order::STATUS_SERVED,
                        'label' => 'Served',
                    ],
                    [
                        'value' => Order::STATUS_COMPLETED,
                        'label' => 'Completed',
                    ],
                    [
                        'value' => Order::STATUS_CANCELED,
                        'label' => 'Canceled',
                    ],
                ],

                'payment_statuses' => [
                    [
                        'value' => Order::PAYMENT_DUE,
                        'label' => 'Due',
                    ],
                    [
                        'value' =>
                            Order::PAYMENT_PARTIALLY_PAID,

                        'label' => 'Partially Paid',
                    ],
                    [
                        'value' => Order::PAYMENT_PAID,
                        'label' => 'Paid',
                    ],
                ],

                'payment_methods' => [
                    [
                        'value' => Order::METHOD_CASH,
                        'label' => 'Cash',
                    ],
                    [
                        'value' => Order::METHOD_CARD,
                        'label' => 'Card',
                    ],
                    [
                        'value' => Order::METHOD_BKASH,
                        'label' => 'bKash',
                    ],
                    [
                        'value' => Order::METHOD_NAGAD,
                        'label' => 'Nagad',
                    ],
                    [
                        'value' =>
                            Order::METHOD_BANK_TRANSFER,

                        'label' => 'Bank Transfer',
                    ],
                    [
                        'value' => Order::METHOD_MIXED,
                        'label' => 'Mixed Payment',
                    ],
                ],
            ],
        ]);
    }
public function editOptions(
    Order $order
): JsonResponse {
    try {

        /*
        |--------------------------------------------------------------------------
        | Load edit options
        |--------------------------------------------------------------------------
        */

        $data = $this->orderService
            ->getEditOptions(
                order: $order,
                userId: request()->user()?->id
            );


        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'message' =>
                'Order edit information loaded successfully.',

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | Existing Order
                |--------------------------------------------------------------------------
                */

                'order' =>
                    new OrderResource(
                        $data['order']
                    ),


                /*
                |--------------------------------------------------------------------------
                | Tables
                |--------------------------------------------------------------------------
                */

                'tables' =>
                    $data['tables'],

                'merge_tables' =>
                    $data['merge_tables'],


                /*
                |--------------------------------------------------------------------------
                | Categories
                |--------------------------------------------------------------------------
                */

                'categories' =>
                    $data['categories'],


                /*
                |--------------------------------------------------------------------------
                | Menu Items
                |--------------------------------------------------------------------------
                */

                'menu_items' =>
                    $data['menu_items'],


                /*
                |--------------------------------------------------------------------------
                | Add-ons
                |--------------------------------------------------------------------------
                */

                'addons' =>
                    $data['addons'],


                /*
                |--------------------------------------------------------------------------
                | Statuses
                |--------------------------------------------------------------------------
                */

                'statuses' =>
                    $data['statuses'],


                /*
                |--------------------------------------------------------------------------
                | Waiter
                |--------------------------------------------------------------------------
                */

                'waiter' =>
                    $data['waiter'],
            ],
        ]);

    } catch (
        ValidationException $exception
    ) {

        throw $exception;

    } catch (
        Throwable $throwable
    ) {

        report(
            $throwable
        );

        return response()->json([
            'success' => false,

            'message' =>
                app()->isLocal()
                    ? $throwable->getMessage()
                    : 'Unable to load the order edit information.',
        ], 500);
    }
}

    public function store(
        StoreOrderRequest $request
    ): JsonResponse {
        try {
            $order = $this->orderService->createOrder(
                data: $request->validated(),
                userId: $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'data' => new OrderResource($order),
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'success' => false,

                'message' => app()->isLocal()
                    ? $throwable->getMessage()
                    : 'Unable to create the order.',
            ], 500);
        }
    }

    public function update(
    UpdateOrderRequest $request,
    Order $order
): JsonResponse {
    try {
        $updatedOrder =
            $this->orderService->updateOrder(
                order: $order,
                data: $request->validated(),
                userId: $request->user()?->id
            );

        return response()->json([
            'success' => true,

            'message' => 'Order updated successfully.',

            'data' => new OrderResource(
                $updatedOrder
            ),
        ]);
    } catch (ValidationException $exception) {
        throw $exception;
    } catch (Throwable $throwable) {

        report($throwable);

        return response()->json([
            'success' => false,

            'message' => app()->isLocal()
                ? $throwable->getMessage()
                : 'Unable to update the order.',
        ], 500);
    }
}

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order
    ): JsonResponse {
        $updatedOrder =
            $this->orderService->updateStatus(
                order: $order,
                newStatus: $request->validated('status')
            );

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => new OrderResource($updatedOrder),
        ]);
    }

    public function cancel(
        CancelOrderRequest $request,
        Order $order
    ): JsonResponse {
        $canceledOrder =
            $this->orderService->cancelOrder(
                order: $order,

                reason: $request->validated(
                    'cancellation_reason'
                )
            );

        return response()->json([
            'success' => true,
            'message' => 'Order canceled successfully.',
            'data' => new OrderResource($canceledOrder),
        ]);
    }

    public function complete(
        Order $order
    ): JsonResponse {
        $completedOrder =
            $this->orderService->completeOrder(
                $order
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Order completed successfully. Customer spending and table availability were updated.',

            'data' =>
                new OrderResource($completedOrder),
        ]);
    }

    private function applyFilters(
        Builder $query,
        array $filters
    ): void {
        $query
            ->when(
                $filters['search'] ?? null,
                function (
                    Builder $builder,
                    string $search
                ): void {
                    $builder->search($search);
                }
            )
            ->when(
                $filters['status'] ?? null,
                function (
                    Builder $builder,
                    string $status
                ): void {
                    $builder->where(
                        'status',
                        $status
                    );
                }
            )
            ->when(
                $filters['payment_status'] ?? null,
                function (
                    Builder $builder,
                    string $paymentStatus
                ): void {
                    $builder->where(
                        'payment_status',
                        $paymentStatus
                    );
                }
            )
            ->when(
                $filters['payment_method'] ?? null,
                function (
                    Builder $builder,
                    string $paymentMethod
                ): void {
                    $builder->where(
                        'payment_method',
                        $paymentMethod
                    );
                }
            )
            ->when(
                $filters['date_from'] ?? null,
                function (
                    Builder $builder,
                    string $dateFrom
                ): void {
                    $builder->whereDate(
                        'created_at',
                        '>=',
                        $dateFrom
                    );
                }
            )
            ->when(
                $filters['date_to'] ?? null,
                function (
                    Builder $builder,
                    string $dateTo
                ): void {
                    $builder->whereDate(
                        'created_at',
                        '<=',
                        $dateTo
                    );
                }
            );
    }
}