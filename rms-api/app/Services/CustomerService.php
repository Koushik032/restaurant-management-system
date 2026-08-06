<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /*
    |--------------------------------------------------------------------------
    | Get Paginated Customer List
    |--------------------------------------------------------------------------
    |
    | Supported filters:
    |
    | - search
    | - status
    | - sort
    | - page
    | - per_page
    |
    */

    public function getCustomers(
        array $filters = []
    ): LengthAwarePaginator {
        return Customer::query()
            ->search(
                $filters['search']
                ?? null
            )
            ->status(
                $filters['status']
                ?? null
            )
            ->sortBy(
                $filters['sort']
                ?? 'latest'
            )
            ->paginate(
                $this->resolvePerPage(
                    $filters
                )
            )
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer Summary
    |--------------------------------------------------------------------------
    |
    | Summary cards:
    |
    | - Total customers
    | - Active customers
    | - New customers this month
    | - Lifetime customer spend
    |
    */

    public function getSummary(): array
    {
        $baseQuery =
            Customer::query();

        $totalCustomers = (int) (
            clone $baseQuery
        )->count();

        $activeCustomers = (int) (
            clone $baseQuery
        )
            ->where(
                'is_active',
                true
            )
            ->count();

        $inactiveCustomers =
            max(
                0,
                $totalCustomers -
                $activeCustomers
            );

        $newCustomersThisMonth = (int) (
            clone $baseQuery
        )
            ->whereBetween(
                'created_at',
                [
                    now()
                        ->copy()
                        ->startOfMonth(),

                    now()
                        ->copy()
                        ->endOfMonth(),
                ]
            )
            ->count();

        $lifetimeSpend = (float) (
            clone $baseQuery
        )->sum(
            'total_spent'
        );

        $totalVisits = (int) (
            clone $baseQuery
        )->sum(
            'total_orders'
        );

        return [
            /*
            |--------------------------------------------------------------------------
            | Raw Values
            |--------------------------------------------------------------------------
            */

            'total_customers' =>
                $totalCustomers,

            'active_customers' =>
                $activeCustomers,

            'inactive_customers' =>
                $inactiveCustomers,

            'new_customers_this_month' =>
                $newCustomersThisMonth,

            'lifetime_spend' =>
                $lifetimeSpend,

            'total_visits' =>
                $totalVisits,

            /*
            |--------------------------------------------------------------------------
            | Formatted Values
            |--------------------------------------------------------------------------
            */

            'lifetime_spend_formatted' =>
                $this->formatMoney(
                    $lifetimeSpend
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Single Customer
    |--------------------------------------------------------------------------
    */

    public function getCustomer(
        Customer $customer
    ): Customer {
        return $customer;
    }

    /*
|--------------------------------------------------------------------------
| Get Customer Details
|--------------------------------------------------------------------------
|
| Customer profile, live order summary এবং paginated visit history
| return করবে।
|
| Supported filters:
|
| - page
| - per_page
|
*/

public function getCustomerDetails(
    Customer $customer,
    array $filters = []
): array {
    $orders =
        $this->getCustomerOrders(
            $customer,
            $filters
        );

    $summary =
        $this->getCustomerOrderSummary(
            $customer
        );

    return [
        'customer' =>
            $customer,

        'summary' =>
            $summary,

        'orders' =>
            $orders,
    ];
}

/*
|--------------------------------------------------------------------------
| Get Customer Orders
|--------------------------------------------------------------------------
|
| Customer-এর full paginated order history load করবে।
| Order item snapshot data এবং add-ons eager load হবে।
|
*/

public function getCustomerOrders(
    Customer $customer,
    array $filters = []
): LengthAwarePaginator {
    return $customer
        ->orders()
        ->with([
            'items' => function (
                $query
            ): void {
                $query
                    ->select([
                        'id',
                        'order_id',
                        'menu_item_id',
                        'menu_item_variant_id',
                        'item_name',
                        'variant_name',
                        'unit_price',
                        'quantity',
                        'addon_total',
                        'line_total',
                        'status',
                        'kitchen_note',
                    ])
                    ->with([
                        'addons',
                    ])
                    ->orderBy(
                        'id'
                    );
            },
        ])
        ->select([
            'id',
            'order_number',
            'customer_id',
            'customer_name',
            'customer_phone',
            'customer_email',
            'status',
            'subtotal',
            'discount_amount',
            'tax_amount',
            'service_charge',
            'total_amount',
            'paid_amount',
            'due_amount',
            'payment_status',
            'payment_method',
            'created_at',
            'completed_at',
            'canceled_at',
        ])
        ->latest(
            'created_at'
        )
        ->paginate(
            $this->resolveCustomerOrderPerPage(
                $filters
            )
        )
        ->withQueryString();
}

/*
|--------------------------------------------------------------------------
| Get Customer Order Summary
|--------------------------------------------------------------------------
|
| Summary সব non-canceled order থেকে live calculate হবে।
|
*/

public function getCustomerOrderSummary(
    Customer $customer
): array {
    $baseQuery =
        $customer
            ->orders()
            ->where(
                'status',
                '!=',
                Order::STATUS_CANCELED
            );

    $visitCount =
        (int) (
            clone $baseQuery
        )->count();

    $totalOrderAmount =
        (float) (
            clone $baseQuery
        )->sum(
            'total_amount'
        );

    $totalPaidAmount =
        (float) (
            clone $baseQuery
        )->sum(
            'paid_amount'
        );

    $totalDueAmount =
        (float) (
            clone $baseQuery
        )->sum(
            'due_amount'
        );

    $firstVisitAt =
        (
            clone $baseQuery
        )
            ->oldest(
                'created_at'
            )
            ->value(
                'created_at'
            );

    $lastVisitAt =
        (
            clone $baseQuery
        )
            ->latest(
                'created_at'
            )
            ->value(
                'created_at'
            );

    return [
        /*
        |--------------------------------------------------------------------------
        | Raw Values
        |--------------------------------------------------------------------------
        */

        'visit_count' =>
            $visitCount,

        'total_order_amount' =>
            $totalOrderAmount,

        'total_paid_amount' =>
            $totalPaidAmount,

        'total_due_amount' =>
            $totalDueAmount,

        'first_visit_at' =>
            $firstVisitAt,

        'last_visit_at' =>
            $lastVisitAt,

        /*
        |--------------------------------------------------------------------------
        | Formatted Values
        |--------------------------------------------------------------------------
        */

        'total_order_amount_formatted' =>
            $this->formatMoney(
                $totalOrderAmount
            ),

        'total_paid_amount_formatted' =>
            $this->formatMoney(
                $totalPaidAmount
            ),

        'total_due_amount_formatted' =>
            $this->formatMoney(
                $totalDueAmount
            ),

        'first_visit_label' =>
            $this->formatDateTime(
                $firstVisitAt
            ),

        'last_visit_label' =>
            $this->formatDateTime(
                $lastVisitAt
            ),
    ];
}

/*
|--------------------------------------------------------------------------
| Resolve Customer Order Pagination Size
|--------------------------------------------------------------------------
*/

private function resolveCustomerOrderPerPage(
    array $filters
): int {
    $perPage =
        (int) (
            $filters['per_page']
            ?? 10
        );

    if ($perPage <= 0) {
        return 10;
    }

    return min(
        $perPage,
        100
    );
}

/*
|--------------------------------------------------------------------------
| Format Date and Time
|--------------------------------------------------------------------------
*/

private function formatDateTime(
    mixed $value
): string {
    if ($value === null) {
        return 'Never';
    }

    try {
        return Carbon::parse(
            $value
        )->format(
            'd M Y, h:i A'
        );
    } catch (\Throwable) {
        return 'Never';
    }
}

    /*
    |--------------------------------------------------------------------------
    | Create Customer
    |--------------------------------------------------------------------------
    |
    | Analytics fields user input থেকে নেওয়া হবে না।
    |
    */

    public function createCustomer(
        array $data
    ): Customer {
        return DB::transaction(
            function () use (
                $data
            ): Customer {
                $customer =
                    Customer::query()
                        ->create([
                            'name' =>
                                $this->normaliseRequiredString(
                                    $data['name']
                                    ?? null
                                ),

                            'phone' =>
                                $this->normaliseNullableString(
                                    $data['phone']
                                    ?? null
                                ),

                            'email' =>
                                $this->normaliseEmail(
                                    $data['email']
                                    ?? null
                                ),

                            'is_active' =>
                                $this->resolveBoolean(
                                    $data['is_active']
                                    ?? true
                                ),

                            'notes' =>
                                $this->normaliseNullableString(
                                    $data['notes']
                                    ?? null
                                ),

                            'total_orders' =>
                                0,

                            'total_spent' =>
                                0,

                            'last_visit_at' =>
                                null,
                        ]);

                return $customer->refresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Customer
    |--------------------------------------------------------------------------
    |
    | total_orders, total_spent ও last_visit_at order lifecycle দ্বারা
    | managed হবে। Customer form থেকে এগুলো update করা হবে না।
    |
    */

    public function updateCustomer(
        Customer $customer,
        array $data
    ): Customer {
        return DB::transaction(
            function () use (
                $customer,
                $data
            ): Customer {
                $customer->fill([
                    'name' =>
                        $this->normaliseRequiredString(
                            $data['name']
                            ?? $customer->name
                        ),

                    'phone' =>
                        $this->normaliseNullableString(
                            array_key_exists(
                                'phone',
                                $data
                            )
                                ? $data['phone']
                                : $customer->phone
                        ),

                    'email' =>
                        $this->normaliseEmail(
                            array_key_exists(
                                'email',
                                $data
                            )
                                ? $data['email']
                                : $customer->email
                        ),

                    'is_active' =>
                        array_key_exists(
                            'is_active',
                            $data
                        )
                            ? $this->resolveBoolean(
                                $data['is_active']
                            )
                            : $customer->is_active,

                    'notes' =>
                        $this->normaliseNullableString(
                            array_key_exists(
                                'notes',
                                $data
                            )
                                ? $data['notes']
                                : $customer->notes
                        ),
                ]);

                $customer->save();

                return $customer->refresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Customer Status
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(
        Customer $customer
    ): Customer {
        $customer->forceFill([
            'is_active' =>
                ! $customer->is_active,
        ])->save();

        return $customer->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Set Customer Status
    |--------------------------------------------------------------------------
    */

    public function setStatus(
        Customer $customer,
        bool $isActive
    ): Customer {
        $customer->forceFill([
            'is_active' =>
                $isActive,
        ])->save();

        return $customer->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Customer
    |--------------------------------------------------------------------------
    |
    | Customer model SoftDeletes ব্যবহার করে।
    | Existing orders delete হবে না।
    |
    */

    public function deleteCustomer(
        Customer $customer
    ): void {
        DB::transaction(
            static function () use (
                $customer
            ): void {
                $customer->delete();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Deleted Customer
    |--------------------------------------------------------------------------
    |
    | Future trash/restore section-এর জন্য method রাখা হয়েছে।
    |
    */

    public function restoreCustomer(
        int $customerId
    ): Customer {
        return DB::transaction(
            static function () use (
                $customerId
            ): Customer {
                $customer =
                    Customer::onlyTrashed()
                        ->findOrFail(
                            $customerId
                        );

                $customer->restore();

                return $customer->refresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Permanently Delete Customer
    |--------------------------------------------------------------------------
    |
    | এখন frontend-এ expose করা হবে না।
    |
    */

    public function permanentlyDeleteCustomer(
        int $customerId
    ): void {
        DB::transaction(
            static function () use (
                $customerId
            ): void {
                $customer =
                    Customer::onlyTrashed()
                        ->findOrFail(
                            $customerId
                        );

                $customer->forceDelete();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Pagination Size
    |--------------------------------------------------------------------------
    */

    private function resolvePerPage(
        array $filters
    ): int {
        $perPage = (int) (
            $filters['per_page']
            ?? 10
        );

        if ($perPage <= 0) {
            return 10;
        }

        return min(
            $perPage,
            100
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalise Required String
    |--------------------------------------------------------------------------
    */

    private function normaliseRequiredString(
        mixed $value
    ): string {
        return trim(
            (string) $value
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalise Nullable String
    |--------------------------------------------------------------------------
    */

    private function normaliseNullableString(
        mixed $value
    ): ?string {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        $resolvedValue =
            trim(
                (string) $value
            );

        return $resolvedValue !== ''
            ? $resolvedValue
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalise Email
    |--------------------------------------------------------------------------
    */

    private function normaliseEmail(
        mixed $value
    ): ?string {
        $email =
            $this->normaliseNullableString(
                $value
            );

        return $email !== null
            ? strtolower(
                $email
            )
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Boolean
    |--------------------------------------------------------------------------
    */

    private function resolveBoolean(
        mixed $value
    ): bool {
        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Format Money
    |--------------------------------------------------------------------------
    */

    private function formatMoney(
        mixed $amount
    ): string {
        return '৳ '
            . number_format(
                is_numeric($amount)
                    ? (float) $amount
                    : 0.0,
                2
            );
    }
}