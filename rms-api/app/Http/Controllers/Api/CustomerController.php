<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerIndexRequest;
use App\Http\Requests\Api\SearchCustomerRequest;
use App\Http\Requests\Api\StoreCustomerRequest;
use App\Http\Requests\Api\UpdateCustomerRequest;
use App\Http\Resources\CustomerDetailsResource;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        private readonly CustomerService $customerService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Customer List
    |--------------------------------------------------------------------------
    |
    | Supported query parameters:
    |
    | - search
    | - status
    | - sort
    | - page
    | - per_page
    |
    */

    public function index(
        CustomerIndexRequest $request
    ): JsonResponse {
        $customers =
            $this->customerService
                ->getCustomers(
                    $request->filters()
                );

        $data =
            CustomerResource::collection(
                $customers->getCollection()
            )->resolve(
                $request
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Customers loaded successfully.',

            'data' =>
                $data,

            'meta' =>
                $this->paginationMeta(
                    $customers
                ),

            'filters' => [
                'statuses' =>
                    $this->statusOptions(),

                'sort_options' =>
                    $this->sortOptions(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Summary
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

    public function summary(): JsonResponse
    {
        return response()->json([
            'success' => true,

            'message' =>
                'Customer summary loaded successfully.',

            'data' =>
                $this->customerService
                    ->getSummary(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Customer
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreCustomerRequest $request
    ): JsonResponse {
        $customer =
            $this->customerService
                ->createCustomer(
                    $request->customerData()
                );

        return response()->json([
            'success' => true,

            'message' =>
                'Customer created successfully.',

            'data' =>
                (new CustomerResource(
                    $customer
                ))->resolve(
                    $request
                ),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Details
    |--------------------------------------------------------------------------
    */

   /*
|--------------------------------------------------------------------------
| Customer Details
|--------------------------------------------------------------------------
|
| Return customer profile,
| live summary and paginated order history.
|
*/

public function show(
    Request $request,
    Customer $customer
): CustomerDetailsResource {

    $filters = [
        'page' =>
            $request->integer(
                'page',
                1
            ),

        'per_page' =>
            $request->integer(
                'per_page',
                10
            ),
    ];


    $customerDetails =
        $this->customerService
            ->getCustomerDetails(
                $customer,
                $filters
            );


    return new CustomerDetailsResource(
        $customerDetails
    );
}

    /*
    |--------------------------------------------------------------------------
    | Update Customer
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer
    ): JsonResponse {
        $updatedCustomer =
            $this->customerService
                ->updateCustomer(
                    $customer,
                    $request->customerData()
                );

        return response()->json([
            'success' => true,

            'message' =>
                'Customer updated successfully.',

            'data' =>
                (new CustomerResource(
                    $updatedCustomer
                ))->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Customer Status
    |--------------------------------------------------------------------------
    */

    public function toggleStatus(
        Request $request,
        Customer $customer
    ): JsonResponse {
        $updatedCustomer =
            $this->customerService
                ->toggleStatus(
                    $customer
                );

        return response()->json([
            'success' => true,

            'message' =>
                $updatedCustomer->is_active
                    ? 'Customer activated successfully.'
                    : 'Customer deactivated successfully.',

            'data' =>
                (new CustomerResource(
                    $updatedCustomer
                ))->resolve(
                    $request
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Customer
    |--------------------------------------------------------------------------
    |
    | Customer soft delete হবে।
    | Existing order records delete হবে না।
    |
    */

    public function destroy(
        Customer $customer
    ): JsonResponse {
        $this->customerService
            ->deleteCustomer(
                $customer
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Customer deleted successfully.',

            'data' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Search Suggestions
    |--------------------------------------------------------------------------
    |
    | Order create/edit form-এর customer lookup-এর জন্য compact suggestion
    | list return করবে।
    |
    */

    public function search(
        SearchCustomerRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        $search = trim(
            (string) (
                $validated['search']
                ?? ''
            )
        );

        $limit = min(
            max(
                (int) (
                    $validated['limit']
                    ?? 10
                ),
                1
            ),
            50
        );

        $customers =
            Customer::query()
                ->active()
                ->search(
                    $search
                )
                ->select([
                    'id',
                    'name',
                    'phone',
                    'email',
                    'last_visit_at',
                    'total_orders',
                    'total_spent',
                    'is_active',
                ])
                ->orderByRaw(
                    '
                    CASE
                        WHEN phone = ? THEN 1
                        WHEN LOWER(email) = ? THEN 2
                        WHEN name = ? THEN 3
                        WHEN name LIKE ? THEN 4
                        ELSE 5
                    END
                    ',
                    [
                        $search,
                        strtolower(
                            $search
                        ),
                        $search,
                        $search . '%',
                    ]
                )
                ->orderBy(
                    'name'
                )
                ->limit(
                    $limit
                )
                ->get();

        $data = $customers
            ->map(
                fn (
                    Customer $customer
                ): array =>
                    $this->transformSearchCustomer(
                        $customer
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'Customer suggestions loaded successfully.',

            'data' =>
                $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Search Customer
    |--------------------------------------------------------------------------
    */

    private function transformSearchCustomer(
        Customer $customer
    ): array {
        $totalSpent =
            $customer->totalSpentValue();

        return [
            'id' =>
                (int) $customer->id,

            'name' =>
                $customer->display_name,

            'phone' =>
                $customer->phone,

            'email' =>
                $customer->email,

            'initial' =>
                $customer->initial,

            'last_visit_at' =>
                $customer
                    ->last_visit_at
                    ?->toISOString(),

            'last_visit_label' =>
                $customer
                    ->last_visit_at
                    ?->format(
                        'd M Y, h:i A'
                    )
                ?? 'Never',

            'total_orders' =>
                $customer->visitCount(),

            'visit_count' =>
                $customer->visitCount(),

            'total_spent' =>
                $totalSpent,

            'total_spent_formatted' =>
                $this->formatMoney(
                    $totalSpent
                ),

            'is_active' =>
                (bool) $customer->is_active,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination Metadata
    |--------------------------------------------------------------------------
    */

    private function paginationMeta(
        mixed $paginator
    ): array {
        return [
            'current_page' =>
                (int) $paginator
                    ->currentPage(),

            'last_page' =>
                (int) $paginator
                    ->lastPage(),

            'per_page' =>
                (int) $paginator
                    ->perPage(),

            'total' =>
                (int) $paginator
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
    | Customer Status Options
    |--------------------------------------------------------------------------
    */

    private function statusOptions(): array
    {
        return [
            [
                'value' => 'all',
                'label' => 'All Customers',
            ],

            [
                'value' => 'active',
                'label' => 'Active',
            ],

            [
                'value' => 'inactive',
                'label' => 'Inactive',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Sort Options
    |--------------------------------------------------------------------------
    */

    private function sortOptions(): array
    {
        return [
            [
                'value' => 'latest',
                'label' => 'Newest First',
            ],

            [
                'value' => 'oldest',
                'label' => 'Oldest First',
            ],

            [
                'value' => 'name_asc',
                'label' => 'Name A–Z',
            ],

            [
                'value' => 'name_desc',
                'label' => 'Name Z–A',
            ],

            [
                'value' => 'visits_high',
                'label' => 'Most Visits',
            ],

            [
                'value' => 'visits_low',
                'label' => 'Fewest Visits',
            ],

            [
                'value' => 'spend_high',
                'label' => 'Highest Spend',
            ],

            [
                'value' => 'spend_low',
                'label' => 'Lowest Spend',
            ],

            [
                'value' => 'last_visit_latest',
                'label' => 'Latest Visit',
            ],

            [
                'value' => 'last_visit_oldest',
                'label' => 'Oldest Visit',
            ],
        ];
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