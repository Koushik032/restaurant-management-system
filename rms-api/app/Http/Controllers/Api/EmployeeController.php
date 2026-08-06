<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEmployeeRequest;
use App\Http\Requests\Api\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Role;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Employee List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $validated = $request->validate([

            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'role_id' => [
                'nullable',
                'integer',
                'exists:roles,id',
            ],

            'status' => [
                'nullable',
                Rule::in(
                    Employee::allowedStatuses()
                ),
            ],

            'account_status' => [
                'nullable',
                Rule::in([
                    'active',
                    'blocked',
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

        ]);

        $perPage = (int) (
            $validated['per_page']
            ??
            10
        );

        $query = Employee::query()
            ->with([
                'user.role',
                'creator',
                'updater',
            ])

            /*
            |--------------------------------------------------------------------------
            | Admin কখনো employee list-এ আসবে না
            |--------------------------------------------------------------------------
            */

            ->whereHas(
                'user.role',
                function ($roleQuery) {
                    $roleQuery->where(
                        'name',
                        '!=',
                        'admin'
                    );
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $validated['search']
                ??
                null
            )
        ) {
            $search = trim(
                $validated['search']
            );

            $query->where(
                function ($employeeQuery) use ($search) {
                    $employeeQuery
                        ->where(
                            'phone',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'user',
                            function ($userQuery) use ($search) {
                                $userQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'username',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'email',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Role Filter
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $validated['role_id']
                ??
                null
            )
        ) {
            $roleId = (int) $validated['role_id'];

            $query->whereHas(
                'user',
                function ($userQuery) use ($roleId) {
                    $userQuery
                        ->where(
                            'role_id',
                            $roleId
                        )
                        ->whereHas(
                            'role',
                            function ($roleQuery) {
                                $roleQuery->where(
                                    'name',
                                    '!=',
                                    'admin'
                                );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Working Status Filter
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $validated['status']
                ??
                null
            )
        ) {
            $query->where(
                'current_status',
                $validated['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Account Status Filter
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $validated['account_status']
                ??
                null
            )
        ) {
            $accountStatus =
                $validated['account_status'];

            $query->whereHas(
                'user',
                function ($userQuery) use ($accountStatus) {
                    if ($accountStatus === 'active') {
                        $userQuery
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNull(
                                'blocked_at'
                            );

                        return;
                    }

                    $userQuery->where(
                        function ($blockedQuery) {
                            $blockedQuery
                                ->where(
                                    'is_active',
                                    false
                                )
                                ->orWhereNotNull(
                                    'blocked_at'
                                );
                        }
                    );
                }
            );
        }

        $employees = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return EmployeeResource::collection(
            $employees
        )->additional([

            'success' => true,

            'message' =>
                'Employees loaded successfully.',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Employee
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreEmployeeRequest $request
    ) {
        $employee =
            $this->employeeService
                ->createEmployee(
                    $request->validated(),
                    $request->user()
                );

        return (
            new EmployeeResource(
                $employee
            )
        )
            ->additional([

                'success' => true,

                'message' =>
                    'Employee created successfully.',

            ])
            ->response()
            ->setStatusCode(201);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Employee
    |--------------------------------------------------------------------------
    */

    public function show(
        Employee $employee
    ) {
        $employee->loadMissing([
            'user.role',
            'creator',
            'updater',
        ]);

        return (
            new EmployeeResource(
                $employee
            )
        )->additional([

            'success' => true,

            'message' =>
                'Employee loaded successfully.',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Employee
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateEmployeeRequest $request,
        Employee $employee
    ) {
        $employee =
            $this->employeeService
                ->updateEmployee(
                    $employee,
                    $request->validated(),
                    $request->user()
                );

        return (
            new EmployeeResource(
                $employee
            )
        )->additional([

            'success' => true,

            'message' =>
                'Employee updated successfully.',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Employee
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        Employee $employee
    ) {
        $this->employeeService
            ->deleteEmployee(
                $employee,
                $request->user()
            );

        return response()->json([

            'success' => true,

            'message' =>
                'Employee deleted successfully.',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Available Employee Roles
    |--------------------------------------------------------------------------
    */

    public function roles()
    {
        $roles = Role::query()
            ->where(
                'name',
                '!=',
                'admin'
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ])
            ->map(
                function (Role $role) {
                    return [

                        'id' =>
                            (int) $role->id,

                        'name' =>
                            $role->name,

                        'label' =>
                            ucwords(
                                str_replace(
                                    [
                                        '_',
                                        '-',
                                    ],
                                    ' ',
                                    $role->name
                                )
                            ),

                    ];
                }
            )
            ->values();

        return response()->json([

            'success' => true,

            'message' =>
                'Employee roles loaded successfully.',

            'data' =>
                $roles,

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Working Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        Request $request,
        Employee $employee
    ) {
        $validated = $request->validate([

            'current_status' => [
                'required',
                'string',
                Rule::in(
                    Employee::allowedStatuses()
                ),
            ],

        ]);

        $employee =
            $this->employeeService
                ->updateCurrentStatus(
                    $employee,
                    $validated['current_status'],
                    $request->user()
                );

        return (
            new EmployeeResource(
                $employee
            )
        )->additional([

            'success' => true,

            'message' =>
                'Employee status updated successfully.',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Explicit Block / Unblock
    |--------------------------------------------------------------------------
    */

    public function updateAccountStatus(
        Request $request,
        Employee $employee
    ) {
        $validated = $request->validate([

            'is_active' => [
                'required',
                'boolean',
            ],

        ]);

        $employee =
            $this->employeeService
                ->updateAccountStatus(
                    $employee,
                    (bool) $validated['is_active'],
                    $request->user()
                );

        return (
            new EmployeeResource(
                $employee
            )
        )->additional([

            'success' => true,

            'message' =>
                $validated['is_active']
                    ? 'Employee account unblocked successfully.'
                    : 'Employee account blocked successfully.',

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Old Toggle Endpoint Compatibility
    |--------------------------------------------------------------------------
    |
    | Existing frontend যদি toggle-active endpoint ব্যবহার করে,
    | সেটিও কাজ করবে।
    |
    */

    public function toggleActive(
        Request $request,
        Employee $employee
    ) {
        $employee->loadMissing('user');

        if (! $employee->user) {
            throw ValidationException::withMessages([

                'employee' =>
                    'The employee account could not be found.',

            ]);
        }

        $newStatus =
            ! (bool) $employee->user->is_active;

        $employee =
            $this->employeeService
                ->updateAccountStatus(
                    $employee,
                    $newStatus,
                    $request->user()
                );

        return (
            new EmployeeResource(
                $employee
            )
        )->additional([

            'success' => true,

            'message' =>
                $newStatus
                    ? 'Employee account unblocked successfully.'
                    : 'Employee account blocked successfully.',

        ]);
    }
}