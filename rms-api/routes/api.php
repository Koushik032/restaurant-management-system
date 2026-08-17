<?php

use App\Http\Controllers\Api\AddOnController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\MenuItemVariantController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RestaurantTableController;
use App\Http\Controllers\Api\OrderOptionsController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderManagement\PaymentController;
use App\Http\Controllers\Api\KitchenOrderController;
use App\Http\Controllers\Api\OrderInvoiceController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ShiftScheduleController;
use App\Http\Controllers\Api\ShiftScheduleOverrideController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\SalaryController;
use App\Http\Controllers\Api\SalaryDetailController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PurchaseOrderPaymentController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\RecipeMappingController;
use App\Http\Controllers\Api\OrderRecipeConsumptionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReportExportController;
/*
|--------------------------------------------------------------------------
| API Health
|--------------------------------------------------------------------------
*/

Route::get('/health', function (): JsonResponse {
    try {
        DB::connection()->getPdo();

        $databaseStatus = 'connected';
    } catch (\Throwable) {
        $databaseStatus = 'disconnected';
    }

    return response()->json(
        [
            'success' =>
                $databaseStatus === 'connected',

            'message' =>
                'Restaurant Management System API health status.',

            'data' => [
                'application' =>
                    config('app.name'),

                'environment' =>
                    app()->environment(),

                'timezone' =>
                    config('app.timezone'),

                'database' =>
                    $databaseStatus,

                'timestamp' =>
                    now()->toISOString(),
            ],

            'errors' => null,
        ],
        $databaseStatus === 'connected'
            ? 200
            : 503
    );
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')
    ->group(function (): void {
        Route::post(
            '/login',
            [
                AuthController::class,
                'login',
            ]
        );

        Route::middleware('auth:sanctum')
            ->group(function (): void {
                Route::get(
                    '/me',
                    [
                        AuthController::class,
                        'me',
                    ]
                );

                Route::post(
                    '/logout',
                    [
                        AuthController::class,
                        'logout',
                    ]
                );

                Route::post(
                    '/logout-all',
                    [
                        AuthController::class,
                        'logoutAll',
                    ]
                );
            });
    });

/*
|--------------------------------------------------------------------------
| Protected Application Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')
    ->group(function (): void {
        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard',
            [
                DashboardController::class,
                'overview',
            ]
        )->middleware(
            'permission:dashboard.view'
        );

        Route::get(
            '/admin/overview',
            [
                DashboardController::class,
                'adminOverview',
            ]
        )->middleware(
            'permission:users.manage'
        );

        Route::get(
            '/manager/overview',
            [
                DashboardController::class,
                'managerOverview',
            ]
        )->middleware(
            'permission:orders.create'
        );

        Route::get(
            '/kitchen/overview',
            [
                DashboardController::class,
                'kitchenOverview',
            ]
        )->middleware(
            'permission:kitchen.view'
        );

        /*
        |--------------------------------------------------------------------------
        | Menu Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('menu-management')
            ->name('menu-management.')
            ->group(function (): void {
                /*
                |--------------------------------------------------------------------------
                | Menu Categories
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/menu-categories/{menuCategory}/status',
                    [
                        MenuCategoryController::class,
                        'toggleStatus',
                    ]
                )->name(
                    'menu-categories.toggle-status'
                );

                Route::apiResource(
                    'menu-categories',
                    MenuCategoryController::class
                )->parameters([
                    'menu-categories' =>
                        'menuCategory',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Menu Items
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/menu-items/{menuItem}/status',
                    [
                        MenuItemController::class,
                        'toggleStatus',
                    ]
                )->name(
                    'menu-items.toggle-status'
                );

                Route::patch(
                    '/menu-items/{menuItem}/featured',
                    [
                        MenuItemController::class,
                        'toggleFeatured',
                    ]
                )->name(
                    'menu-items.toggle-featured'
                );

                Route::apiResource(
                    'menu-items',
                    MenuItemController::class
                )->parameters([
                    'menu-items' =>
                        'menuItem',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Menu Item Variants
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/menu-variants/{menuVariant}/status',
                    [
                        MenuItemVariantController::class,
                        'toggleStatus',
                    ]
                )->name(
                    'menu-variants.toggle-status'
                );

                Route::apiResource(
                    'menu-variants',
                    MenuItemVariantController::class
                )->parameters([
                    'menu-variants' =>
                        'menuVariant',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Add-ons
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/add-ons/{addOn}/status',
                    [
                        AddOnController::class,
                        'toggleStatus',
                    ]
                )->name(
                    'add-ons.toggle-status'
                );

                Route::apiResource(
                    'add-ons',
                    AddOnController::class
                )->parameters([
                    'add-ons' => 'addOn',
                ]);
            });

        /*
        |--------------------------------------------------------------------------
        | Table Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('table-management')
            ->name('table-management.')
            ->group(function (): void {
                /*
                |--------------------------------------------------------------------------
                | Restaurant table list and create
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/tables',
                    [
                        RestaurantTableController::class,
                        'index',
                    ]
                )->name('tables.index');

                Route::post(
                    '/tables',
                    [
                        RestaurantTableController::class,
                        'store',
                    ]
                )->name('tables.store');

                /*
                |--------------------------------------------------------------------------
                | Merge and split
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/tables/{restaurantTable}/merge-options',
                    [
                        RestaurantTableController::class,
                        'mergeOptions',
                    ]
                )->name(
                    'tables.merge-options'
                );

                Route::post(
                    '/tables/{restaurantTable}/merge',
                    [
                        RestaurantTableController::class,
                        'merge',
                    ]
                )->name('tables.merge');

                Route::post(
                    '/tables/{restaurantTable}/split',
                    [
                        RestaurantTableController::class,
                        'split',
                    ]
                )->name('tables.split');

                /*
                |--------------------------------------------------------------------------
                | Edit options
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/tables/{restaurantTable}/edit-options',
                    [
                        RestaurantTableController::class,
                        'editOptions',
                    ]
                )->name(
                    'tables.edit-options'
                );

                /*
                |--------------------------------------------------------------------------
                | Single table CRUD
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/tables/{restaurantTable}',
                    [
                        RestaurantTableController::class,
                        'show',
                    ]
                )->name('tables.show');

                Route::put(
                    '/tables/{restaurantTable}',
                    [
                        RestaurantTableController::class,
                        'update',
                    ]
                )->name('tables.update');

                Route::delete(
                    '/tables/{restaurantTable}',
                    [
                        RestaurantTableController::class,
                        'destroy',
                    ]
                )->name('tables.destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Order Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('order-management')
            ->name('order-management.')
            ->group(function (): void {

                Route::get(
                    '/orders/{order}/edit-options',
                    [
                        OrderController::class,
                        'editOptions',
                    ]
                )->name('orders.edit-options');

                Route::get(
                    '/payments',
                    [PaymentController::class, 'index']
                );

                Route::post(
                    '/payments',
                    [PaymentController::class, 'store']
                );
                /*
                |--------------------------------------------------------------------------
                | Download Order Invoice
                |--------------------------------------------------------------------------
                |
                | একটি নির্দিষ্ট order-এর customer invoice PDF generate এবং download করবে।
                |
                */

                Route::get(
                    '/orders/{order}/invoice',
                    [
                        OrderInvoiceController::class,
                        'download',
                    ]
                )
                    ->middleware(
                        'permission:orders.view'
                    )
                    ->name(
                        'orders.invoice.download'
                    );

                /*
                |--------------------------------------------------------------------------
                | Create Order Options
                |--------------------------------------------------------------------------
                */
                /*
                |--------------------------------------------------------------------------
                | Update Order
                |--------------------------------------------------------------------------
                */

                Route::put(
                    '/orders/{order}',
                    [
                        OrderController::class,
                        'update',
                    ]
                )->name('orders.update');

                Route::get(
                    '/create-options',
                    [
                        OrderController::class,
                        'createOptions',
                    ]
                )->name(
                    'create-options'
                );


                /*
                |--------------------------------------------------------------------------
                | Order list and create
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/orders',
                    [
                        OrderController::class,
                        'index',
                    ]
                )->name(
                    'orders.index'
                );


                Route::post(
                    '/orders',
                    [
                        OrderController::class,
                        'store',
                    ]
                )->name(
                    'orders.store'
                );


                /*
                |--------------------------------------------------------------------------
                | Order actions
                |--------------------------------------------------------------------------
                */


                Route::patch(
                    '/orders/{order}/status',
                    [
                        OrderController::class,
                        'updateStatus',
                    ]
                )->name(
                    'orders.update-status'
                );


                Route::post(
                    '/orders/{order}/cancel',
                    [
                        OrderController::class,
                        'cancel',
                    ]
                )->name(
                    'orders.cancel'
                );


                Route::post(
                    '/orders/{order}/complete',
                    [
                        OrderController::class,
                        'complete',
                    ]
                )->name(
                    'orders.complete'
                );


                /*
                |--------------------------------------------------------------------------
                | Single Order
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/orders/{order}',
                    [
                        OrderController::class,
                        'show',
                    ]
                )->name(
                    'orders.show'
                );
            });
            /*
        |--------------------------------------------------------------------------
        | Supplier Management
        |--------------------------------------------------------------------------
        */


        Route::apiResource(
            'suppliers',
            SupplierController::class
        );

        /*
    |--------------------------------------------------------------------------
    | Expense Management
    |--------------------------------------------------------------------------
    */


    Route::middleware(
        'permission:expenses.manage'
    )
    ->prefix('expenses')
    ->group(function () {


        /*
        |--------------------------------------------------------------------------
        | List
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/',
            [
                ExpenseController::class,
                'index'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Summary Cards
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/summary',
            [
                ExpenseController::class,
                'summary'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Dropdown Options
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/options',
            [
                ExpenseController::class,
                'options'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Create
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/',
            [
                ExpenseController::class,
                'store'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | View Single Expense
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/{expense}',
            [
                ExpenseController::class,
                'show'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/{expense}',
            [
                ExpenseController::class,
                'update'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Soft Delete
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/{expense}',
            [
                ExpenseController::class,
                'destroy'
            ]
        );


    });
    /*
    |--------------------------------------------------------------------------
    | Purchase Order Management
    |--------------------------------------------------------------------------
    */

    Route::patch(
        'purchase-orders/{purchaseOrder}/status',
        [
            PurchaseOrderController::class,
            'updateStatus',
        ]
    )->name(
        'purchase-orders.update-status'
    );


    Route::post(
        'purchase-orders/{purchaseOrder}/receive',
        [
            PurchaseOrderController::class,
            'receive',
        ]
    )->name(
        'purchase-orders.receive'
    );


    /*
    |--------------------------------------------------------------------------
    | Purchase Order Payment History
    |--------------------------------------------------------------------------
    |
    | Payment history is intentionally immutable from the API.
    |
    | GET  = view payment history
    | POST = record a new payment
    |
    */

    Route::prefix(
        'purchase-orders'
    )
        ->name(
            'purchase-orders.'
        )
        ->controller(
            PurchaseOrderPaymentController::class
        )
        ->group(function (): void {

            Route::get(
                '/{purchaseOrder}/payments',
                'index'
            )->name(
                'payments.index'
            );


            Route::post(
                '/{purchaseOrder}/payments',
                'store'
            )->name(
                'payments.store'
            );
        });

    /*
|--------------------------------------------------------------------------
| Purchase Order GRN / Receipt History
|--------------------------------------------------------------------------
*/

Route::get(
    'purchase-orders/{purchaseOrder}/receipts',
    [
        PurchaseOrderController::class,
        'receipts',
    ]
)->name(
    'purchase-orders.receipts.index'
);
    /*
    |--------------------------------------------------------------------------
    | Purchase Order CRUD
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'purchase-orders',
        PurchaseOrderController::class
    )->parameters([
        'purchase-orders' =>
            'purchaseOrder',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Inventory Management
    |--------------------------------------------------------------------------
    |
    | Permission rules are enforced by InventoryController,
    | StockTransferController and their FormRequest classes.
    |
    | Keeping permission checks there allows:
    |
    | inventory.view   -> read-only inventory access
    | inventory.manage -> read + management access
    |
    | without forcing a manage-only user to also possess inventory.view.
    |
    */

    Route::prefix('inventory')
        ->name('inventory.')
        ->group(function (): void {

            /*
            |--------------------------------------------------------------------------
            | Inventory / Warehouse
            |--------------------------------------------------------------------------
            */

            Route::controller(
                InventoryController::class
            )
                ->group(function (): void {

                    /*
                    |--------------------------------------------------------------------------
                    | Summary and Options
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/summary',
                        'summary'
                    )->name(
                        'summary'
                    );


                    Route::get(
                        '/options',
                        'options'
                    )->name(
                        'options'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Warehouse Stocks
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/warehouse-stocks',
                        'warehouseStocks'
                    )->name(
                        'warehouse-stocks.index'
                    );


                    Route::get(
                        '/warehouse-stocks/{rawMaterial}',
                        'showWarehouseStock'
                    )->name(
                        'warehouse-stocks.show'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Stock Movement History
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/stock-movements',
                        'stockMovements'
                    )->name(
                        'stock-movements.index'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Raw Material List and Create
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/raw-materials',
                        'rawMaterials'
                    )->name(
                        'raw-materials.index'
                    );


                    Route::post(
                        '/raw-materials',
                        'storeRawMaterial'
                    )->name(
                        'raw-materials.store'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Raw Material Actions
                    |--------------------------------------------------------------------------
                    |
                    | Static action routes stay before the dynamic show route.
                    |
                    */

                    Route::patch(
                        '/raw-materials/{rawMaterial}/status',
                        'toggleRawMaterialStatus'
                    )->name(
                        'raw-materials.toggle-status'
                    );


                    Route::post(
                        '/raw-materials/{rawMaterial}/warehouse-adjustment',
                        'adjustWarehouseStock'
                    )->name(
                        'raw-materials.warehouse-adjustment'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Raw Material Single CRUD
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/raw-materials/{rawMaterial}',
                        'showRawMaterial'
                    )->name(
                        'raw-materials.show'
                    );


                    Route::put(
                        '/raw-materials/{rawMaterial}',
                        'updateRawMaterial'
                    )->name(
                        'raw-materials.update'
                    );


                    Route::delete(
                        '/raw-materials/{rawMaterial}',
                        'destroyRawMaterial'
                    )->name(
                        'raw-materials.destroy'
                    );
                });

                        /*
            |--------------------------------------------------------------------------
            | Recipe Mapping
            |--------------------------------------------------------------------------
            |
            | GET:
            |     inventory.view OR inventory.manage
            |
            | PUT / DELETE:
            |     inventory.manage
            |
            | Supports both Menu Items and Add-ons.
            |
            | Legacy Menu Item routes are preserved for backward compatibility.
            |
            */

            Route::controller(
                RecipeMappingController::class
            )
                ->group(function (): void {

                    /*
                    |--------------------------------------------------------------------------
                    | Recipe Mapping List
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/recipe-mappings',
                        'index'
                    )->name(
                        'recipe-mappings.index'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Unified Save / Replace
                    |--------------------------------------------------------------------------
                    |
                    | Body:
                    |
                    | target_type = menu_item | add_on
                    | target_id
                    | ingredients[]
                    |
                    */

                    Route::put(
                        '/recipe-mappings',
                        'saveTarget'
                    )->name(
                        'recipe-mappings.save'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Unified Target Show
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/recipe-mappings/{targetType}/{targetId}',
                        'showTarget'
                    )
                        ->where(
                            'targetType',
                            'menu_item|add_on'
                        )
                        ->whereNumber(
                            'targetId'
                        )
                        ->name(
                            'recipe-mappings.target.show'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Unified Target Delete
                    |--------------------------------------------------------------------------
                    |
                    | Deletes only recipe definition rows.
                    | Menu Item / Add-on master records remain untouched.
                    |
                    */

                    Route::delete(
                        '/recipe-mappings/{targetType}/{targetId}',
                        'destroyTarget'
                    )
                        ->where(
                            'targetType',
                            'menu_item|add_on'
                        )
                        ->whereNumber(
                            'targetId'
                        )
                        ->name(
                            'recipe-mappings.target.destroy'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Legacy Menu Item Show
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/recipe-mappings/{menuItem}',
                        'show'
                    )
                        ->whereNumber(
                            'menuItem'
                        )
                        ->name(
                            'recipe-mappings.show'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Legacy Menu Item Save / Replace
                    |--------------------------------------------------------------------------
                    */

                    Route::put(
                        '/recipe-mappings/{menuItem}',
                        'update'
                    )
                        ->whereNumber(
                            'menuItem'
                        )
                        ->name(
                            'recipe-mappings.update'
                        );
                });
                /*
            |--------------------------------------------------------------------------
            | Order Recipe Consumption Audit
            |--------------------------------------------------------------------------
            |
            | Read-only immutable recipe consumption history for a specific order.
            |
            | GET:
            |     inventory.view OR inventory.manage
            |
            | Permission is enforced by OrderRecipeConsumptionController.
            |
            */

            Route::get(
                '/orders/{order}/recipe-consumption',
                [
                    OrderRecipeConsumptionController::class,
                    'showForOrder',
                ]
            )->name(
                'orders.recipe-consumption.show'
            );

            /*
            |--------------------------------------------------------------------------
            | Warehouse → Restaurant Stock Transfer
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | This is already inside /inventory.
            | Do not add another inventory prefix here.
            |
            */

            Route::controller(
                StockTransferController::class
            )
                ->group(function (): void {

                    /*
                    |--------------------------------------------------------------------------
                    | Restaurant Stocks
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/restaurant-stocks',
                        'restaurantStocks'
                    )->name(
                        'restaurant-stocks.index'
                    );


                    Route::get(
                        '/restaurant-stocks/{rawMaterial}',
                        'showRestaurantStock'
                    )->name(
                        'restaurant-stocks.show'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Stock Transfer History
                    |--------------------------------------------------------------------------
                    */

                    Route::get(
                        '/stock-transfers',
                        'index'
                    )->name(
                        'stock-transfers.index'
                    );


                    Route::get(
                        '/stock-transfers/{stockTransfer}',
                        'show'
                    )->name(
                        'stock-transfers.show'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Stock Transfer
                    |--------------------------------------------------------------------------
                    |
                    | StoreStockTransferRequest requires inventory.manage.
                    |
                    */

                    Route::post(
                        '/stock-transfers',
                        'store'
                    )->name(
                        'stock-transfers.store'
                    );
                });
        });


        /*
|--------------------------------------------------------------------------
| Customer Management
|--------------------------------------------------------------------------
|
| Customer list, summary, search, create, details, update,
| status toggle এবং soft delete manage করবে।
|
*/
Route::prefix('customers')
    ->name('customers.')
    ->middleware(
        'permission:customers.manage'
    )
    ->group(function (): void {
        /*
        |--------------------------------------------------------------------------
        | Customer Summary
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/summary',
            [
                CustomerController::class,
                'summary',
            ]
        )->name(
            'summary'
        );

        /*
        |--------------------------------------------------------------------------
        | Customer Search Suggestions
        |--------------------------------------------------------------------------
        |
        | Order create/edit form customer lookup-এর জন্য।
        |
        */

        Route::get(
            '/search',
            [
                CustomerController::class,
                'search',
            ]
        )->name(
            'search'
        );

        /*
        |--------------------------------------------------------------------------
        | Customer List
        |--------------------------------------------------------------------------
        |
        | Query parameters:
        |
        | - search
        | - status
        | - sort
        | - page
        | - per_page
        |
        */

        Route::get(
            '/',
            [
                CustomerController::class,
                'index',
            ]
        )->name(
            'index'
        );

        /*
        |--------------------------------------------------------------------------
        | Create Customer
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/',
            [
                CustomerController::class,
                'store',
            ]
        )->name(
            'store'
        );

        /*
        |--------------------------------------------------------------------------
        | Toggle Customer Status
        |--------------------------------------------------------------------------
        |
        | Dynamic customer route-এর আগে থাকবে।
        |
        */

        Route::patch(
            '/{customer}/status',
            [
                CustomerController::class,
                'toggleStatus',
            ]
        )->name(
            'toggle-status'
        );

        /*
        |--------------------------------------------------------------------------
        | Customer Details
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/{customer}',
            [
                CustomerController::class,
                'show',
            ]
        )->name(
            'show'
        );

        /*
        |--------------------------------------------------------------------------
        | Update Customer
        |--------------------------------------------------------------------------
        */

        Route::put(
            '/{customer}',
            [
                CustomerController::class,
                'update',
            ]
        )->name(
            'update'
        );

        /*
        |--------------------------------------------------------------------------
        | Soft Delete Customer
        |--------------------------------------------------------------------------
        */

        Route::delete(
            '/{customer}',
            [
                CustomerController::class,
                'destroy',
            ]
        )->name(
            'destroy'
        );
    });

        /*
    |--------------------------------------------------------------------------
    | Kitchen Order Management
    |--------------------------------------------------------------------------
    |
    | Kitchen display, chef assignment এবং kitchen status progression:
    |
    | Pending
    |   → Accept Order
    |   → Start Preparing
    |   → Mark Ready
    |
    */

    Route::prefix('kitchen')
        ->name('kitchen.')
        ->controller(KitchenOrderController::class)
        ->group(function (): void {
            /*
            |--------------------------------------------------------------------------
            | Kitchen Order List
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/orders',
                'index'
            )->name('orders.index');

            /*
            |--------------------------------------------------------------------------
            | Kitchen Order Details
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/orders/{order}',
                'show'
            )->name('orders.show');

            /*
            |--------------------------------------------------------------------------
            | Accept Kitchen Order
            |--------------------------------------------------------------------------
            |
            | Authenticated chef-এর user ID order-এর chef_id field-এ save হবে।
            | sent_to_kitchen_at timestamp update হবে।
            |
            */

            Route::post(
                '/orders/{order}/accept',
                'accept'
            )->name('orders.accept');

            /*
            |--------------------------------------------------------------------------
            | Start Preparing
            |--------------------------------------------------------------------------
            |
            | Order status preparing হবে এবং preparing_at update হবে।
            |
            */

            Route::post(
                '/orders/{order}/start-preparing',
                'startPreparing'
            )->name(
                'orders.start-preparing'
            );

            /*
            |--------------------------------------------------------------------------
            | Mark Order Ready
            |--------------------------------------------------------------------------
            |
            | Order status ready হবে এবং ready_at update হবে।
            |
            */

            Route::post(
                '/orders/{order}/ready',
                'markReady'
            )->name('orders.ready');
        });
        /*
        |--------------------------------------------------------------------------
        | Billing and Statement Module
        |--------------------------------------------------------------------------
        |
        | Billing summary, settlement orders, payment methods এবং staff activity
        | report এই route group থেকে manage করা হবে।
        |
        */

        Route::prefix('billing')
            ->name('billing.')
            ->middleware('permission:billing.view')
            ->group(function (): void {
            /*
            |--------------------------------------------------------------------------
            | Billing Summary
            |--------------------------------------------------------------------------
            |
            | Selected date বা date range অনুযায়ী net sales, collected amount,
            | tax, service charge, cash collection, due এবং total order summary
            | return করবে।
            |
            | Query parameters:
            | - date
            | - date_from
            | - date_to
            |
            */

            Route::get(
                '/summary',
                [
                    BillingController::class,
                    'summary',
                ]
            )->name('summary');

            /*
            |--------------------------------------------------------------------------
            | Settlement Orders
            |--------------------------------------------------------------------------
            |
            | Order status বা payment status অনুযায়ী settlement order list return
            | করবে। প্রতিটি page-এ default 5টি order দেখানো হবে।
            |
            | Query parameters:
            | - date
            | - date_from
            | - date_to
            | - status
            | - page
            | - per_page
            |
            */

            Route::get(
                '/settlements',
                [
                    BillingController::class,
                    'settlements',
                ]
            )->name('settlements');

            /*
            |--------------------------------------------------------------------------
            | Payment Mode Report
            |--------------------------------------------------------------------------
            |
            | order_payments table থেকে payment method অনুযায়ী payment list
            | return করবে।
            |
            | Query parameters:
            | - date
            | - date_from
            | - date_to
            | - payment_method
            | - page
            | - per_page
            |
            */

            Route::get(
                '/payment-modes',
                [
                    BillingController::class,
                    'paymentModes',
                ]
            )->name('payment-modes');

            /*
            |--------------------------------------------------------------------------
            | Payment and Staff Activity
            |--------------------------------------------------------------------------
            |
            | Order creator, assigned chef এবং payment receiver informationসহ
            | payment activity list return করবে।
            |
            | orders.created_by user-কে waiter/order creator হিসেবে দেখানো হবে।
            |
            | Query parameters:
            | - date
            | - date_from
            | - date_to
            | - user_id
            | - user_type
            | - page
            | - per_page
            |
            */

            Route::get(
                '/payment-activities',
                [
                    BillingController::class,
                    'paymentActivities',
                ]
            )->name('payment-activities');

            /*
            |--------------------------------------------------------------------------
            | Billing Users
            |--------------------------------------------------------------------------
            |
            | User filter dropdown-এর জন্য creator, chef এবং payment receiver
            | হিসেবে ব্যবহৃত users return করবে।
            |
            */

            Route::get(
                '/users',
                [
                    BillingController::class,
                    'users',
                ]
            )->name('users');
        });

        /*
        |--------------------------------------------------------------------------
        | Staff Management
        |--------------------------------------------------------------------------
        |
        | Employee, Attendance and Shift Schedule routes use the /api/staff prefix.
        |
        */

        Route::prefix('staff')
            ->controller(EmployeeController::class)
            ->group(function (): void {

                /*
                |--------------------------------------------------------------------------
                | Roles
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/roles',
                    'roles'
                );

                /*
                |--------------------------------------------------------------------------
                | Employee CRUD
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/employees',
                    'index'
                );

                Route::post(
                    '/employees',
                    'store'
                );

                Route::get(
                    '/employees/{employee}',
                    'show'
                );

                Route::put(
                    '/employees/{employee}',
                    'update'
                );

                Route::patch(
                    '/employees/{employee}',
                    'update'
                );

                Route::delete(
                    '/employees/{employee}',
                    'destroy'
                );

                /*
                |--------------------------------------------------------------------------
                | Employee Working Status
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/employees/{employee}/status',
                    'updateStatus'
                );

                /*
                |--------------------------------------------------------------------------
                | Block / Unblock Employee Account
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/employees/{employee}/account-status',
                    'updateAccountStatus'
                );

                /*
                |--------------------------------------------------------------------------
                | Temporary Old Frontend Compatibility
                |--------------------------------------------------------------------------
                */

                Route::patch(
                    '/employees/{employee}/toggle-active',
                    'toggleActive'
                );

                /*
                |--------------------------------------------------------------------------
                | Shift Schedules
                |--------------------------------------------------------------------------
                */

                Route::prefix('shift-schedules')
                    ->controller(ShiftScheduleController::class)
                    ->group(function (): void {

                        Route::get(
                            '/employees',
                            'employees'
                        );

                        Route::get(
                            '/',
                            'index'
                        );

                        Route::post(
                            '/',
                            'store'
                        );

                        Route::get(
                            '/{shiftSchedule}',
                            'show'
                        );

                        Route::put(
                            '/{shiftSchedule}',
                            'update'
                        );

                        Route::patch(
                            '/{shiftSchedule}',
                            'update'
                        );

                        Route::patch(
                            '/{shiftSchedule}/status',
                            'updateStatus'
                        );

                        Route::delete(
                            '/{shiftSchedule}',
                            'destroy'
                        );
                    });

                /*
                |--------------------------------------------------------------------------
                | One-Day Schedule Overrides
                |--------------------------------------------------------------------------
                */

                Route::controller(
                    ShiftScheduleOverrideController::class
                )->group(function (): void {

                    Route::get(
                        '/shift-schedules/{shiftSchedule}/overrides',
                        'index'
                    );

                    Route::post(
                        '/shift-schedules/{shiftSchedule}/overrides',
                        'store'
                    );

                    Route::put(
                        '/shift-schedule-overrides/{shiftScheduleOverride}',
                        'update'
                    );

                    Route::patch(
                        '/shift-schedule-overrides/{shiftScheduleOverride}',
                        'update'
                    );

                    Route::delete(
                        '/shift-schedule-overrides/{shiftScheduleOverride}',
                        'destroy'
                    );
                });

                /*
                |--------------------------------------------------------------------------
                | Attendance
                |--------------------------------------------------------------------------
                */

                Route::prefix('attendances')
                    ->controller(AttendanceController::class)
                    ->group(function (): void {

                        /*
                        | Static route must remain before /{attendance}.
                        */

                        Route::post(
                            '/sync',
                            'sync'
                        );

                        Route::get(
                            '/',
                            'index'
                        );

                        Route::get(
                            '/{attendance}',
                            'show'
                        );
                    });
            });

        /*
        |--------------------------------------------------------------------------
        | Admin Salary Management
        |--------------------------------------------------------------------------
        |
        | Important:
        | This group must remain OUTSIDE Route::prefix('staff').
        | Final endpoint: /api/admin/salaries
        |
        */

        Route::prefix('admin/salaries')
            ->controller(SalaryController::class)
            ->group(function (): void {

                /*
                | Static routes must remain before /{salary}.
                */

                Route::get(
                    '/employees',
                    'employees'
                );

                Route::post(
                    '/generate',
                    'generate'
                );

                Route::get(
                    '/',
                    'index'
                );

                Route::put(
                    '/{salary}',
                    'update'
                );

                Route::patch(
                    '/{salary}/payment-status',
                    'paymentStatus'
                );

                Route::delete(
                    '/{salary}',
                    'destroy'
                );
            });
            /*
            |--------------------------------------------------------------------------
            | Admin Salary Details
            |--------------------------------------------------------------------------
            */

            Route::prefix('admin/salary-details')
                ->controller(SalaryDetailController::class)
                ->group(function (): void {

                    Route::get(
                        '/',
                        'index'
                    );

                    Route::patch(
                        '/{salaryDetail}',
                        'update'
                    );

                });
            /*
|--------------------------------------------------------------------------
| Reports Module
|--------------------------------------------------------------------------
*/


Route::prefix('reports')
    ->name('reports.')
    ->middleware('permission:reports.view')
    ->group(function (): void {


        /*
        |--------------------------------------------------------------------------
        | Report View
        |--------------------------------------------------------------------------
        */


        Route::controller(
            ReportController::class
        )
        ->group(function (): void {


            Route::get(
                '/orders',
                'orders'
            )->name('orders');



            Route::get(
                '/expenses',
                'expenses'
            )->name('expenses');



            Route::get(
                '/expenses/summary',
                'expenseSummary'
            )->name('expenses.summary');



            Route::get(
                '/purchase-orders',
                'purchaseOrders'
            )->name('purchase-orders');



            Route::get(
                '/restaurant-stock',
                'restaurantStock'
            )->name('restaurant-stock');



            Route::get(
                '/warehouse-stock',
                'warehouseStock'
            )->name('warehouse-stock');



            Route::get(
                '/stock-transfers',
                'stockTransfers'
            )->name('stock-transfers');

            Route::get(
    '/attendance/employees',
    'attendanceEmployees'
)->name('attendance.employees');

            Route::get(
                '/attendance',
                'attendance'
            )->name('attendance');


        });



        /*
        |--------------------------------------------------------------------------
        | CSV Export
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/{type}/export/csv',
            [
                ReportExportController::class,
                'csv'
            ]
        )
        ->name('export.csv');



        /*
        |--------------------------------------------------------------------------
        | PDF Export
        |--------------------------------------------------------------------------
        */


        Route::get(
            '/{type}/export/pdf',
            [
                ReportExportController::class,
                'pdf'
            ]
        )
        ->name('export.pdf');


    });
    });