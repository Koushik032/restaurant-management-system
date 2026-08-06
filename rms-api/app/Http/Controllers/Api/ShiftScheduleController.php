<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreShiftScheduleRequest;
use App\Http\Requests\Api\UpdateShiftScheduleRequest;
use App\Http\Resources\ShiftScheduleResource;
use App\Models\Employee;
use App\Models\ShiftSchedule;
use App\Models\ShiftScheduleOverride;
use App\Services\ShiftScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftScheduleController extends Controller
{
    public function __construct(
        private readonly ShiftScheduleService $shiftScheduleService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Effective Daily Schedule List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $validated =
            $request->validate([

                'schedule_date' => [
                    'nullable',
                    'date',
                ],

                'search' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'employee_id' => [
                    'nullable',
                    'integer',
                    'exists:employees,id',
                ],

                'role_id' => [
                    'nullable',
                    'integer',
                    'exists:roles,id',
                ],

                'status' => [
                    'nullable',

                    Rule::in([
                        'active',
                        'inactive',
                        'regular',
                        'modified',
                        'day_off',
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

        $selectedDate =
            Carbon::parse(
                $validated['schedule_date']
                ??
                now()->format('Y-m-d')
            );

        $dateString =
            $selectedDate->format('Y-m-d');

        $dayName =
            strtolower(
                $selectedDate->format('l')
            );

        $perPage =
            (int) (
                $validated['per_page']
                ??
                10
            );

        $query =
            ShiftSchedule::query()
                ->with([

                    'employee.user.role',

                    'overrides' =>
                        function ($overrideQuery) use (
                            $dateString
                        ) {

                            $overrideQuery
                                ->whereDate(
                                    'override_date',
                                    $dateString
                                );

                        },

                    'creator',

                    'updater',

                ])
                ->whereDate(
                    'start_date',
                    '<=',
                    $dateString
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    $dateString
                )
                ->whereHas(
                    'employee.user.role',
                    function ($roleQuery) {

                        $roleQuery->where(
                            'name',
                            '!=',
                            'admin'
                        );

                    }
                )
                ->where(
                    function ($scheduleQuery) use (
                        $dayName,
                        $dateString
                    ) {

                        /*
                        | Regular working day
                        */

                        $scheduleQuery
                            ->whereJsonContains(
                                'working_days',
                                $dayName
                            )

                            /*
                            | অথবা regular off-day হলেও
                            | modified extra shift রয়েছে।
                            */

                            ->orWhereHas(
                                'overrides',
                                function ($overrideQuery) use (
                                    $dateString
                                ) {

                                    $overrideQuery
                                        ->whereDate(
                                            'override_date',
                                            $dateString
                                        )
                                        ->where(
                                            'override_type',
                                            ShiftScheduleOverride::TYPE_MODIFIED
                                        );

                                }
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

            $search =
                trim(
                    $validated['search']
                );

            $query->whereHas(
                'employee',
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
        | Employee Filter
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $validated['employee_id']
                ??
                null
            )
        ) {

            $query->where(
                'employee_id',
                $validated['employee_id']
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

            $roleId =
                (int) $validated['role_id'];

            $query->whereHas(
                'employee.user',
                function ($userQuery) use ($roleId) {

                    $userQuery->where(
                        'role_id',
                        $roleId
                    );

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Status / Override Filter
        |--------------------------------------------------------------------------
        */

        $status =
            $validated['status']
            ??
            null;

        if ($status === 'inactive') {

            $query->where(
                'is_active',
                false
            );

        }

        if ($status === 'active') {

            $query
                ->where(
                    'is_active',
                    true
                )
                ->whereDoesntHave(
                    'overrides',
                    function ($overrideQuery) use (
                        $dateString
                    ) {

                        $overrideQuery
                            ->whereDate(
                                'override_date',
                                $dateString
                            )
                            ->where(
                                'override_type',
                                ShiftScheduleOverride::TYPE_DAY_OFF
                            );

                    }
                );

        }

        if ($status === 'regular') {

            $query
                ->where(
                    'is_active',
                    true
                )
                ->whereDoesntHave(
                    'overrides',
                    function ($overrideQuery) use (
                        $dateString
                    ) {

                        $overrideQuery->whereDate(
                            'override_date',
                            $dateString
                        );

                    }
                );

        }

        if ($status === 'modified') {

            $query->whereHas(
                'overrides',
                function ($overrideQuery) use (
                    $dateString
                ) {

                    $overrideQuery
                        ->whereDate(
                            'override_date',
                            $dateString
                        )
                        ->where(
                            'override_type',
                            ShiftScheduleOverride::TYPE_MODIFIED
                        );

                }
            );

        }

        if ($status === 'day_off') {

            $query->whereHas(
                'overrides',
                function ($overrideQuery) use (
                    $dateString
                ) {

                    $overrideQuery
                        ->whereDate(
                            'override_date',
                            $dateString
                        )
                        ->where(
                            'override_type',
                            ShiftScheduleOverride::TYPE_DAY_OFF
                        );

                }
            );

        }


        $schedules =
            $query
                ->orderBy('start_time')
                ->orderBy('employee_id')
                ->paginate($perPage)
                ->withQueryString();

        return ShiftScheduleResource::collection(
            $schedules
        )->additional([

            'success' =>
                true,

            'message' =>
                'Daily shift schedules loaded successfully.',

            'selected_date' =>
                $dateString,

            'selected_day' =>
                $selectedDate->format('l'),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Available Employees
    |--------------------------------------------------------------------------
    */

    public function employees()
    {
        $employees =
            Employee::query()
                ->with([
                    'user.role',
                ])
                ->whereHas(
                    'user',
                    function ($userQuery) {

                        $userQuery
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNull(
                                'blocked_at'
                            )
                            ->whereHas(
                                'role',
                                function ($roleQuery) {

                                    $roleQuery
                                        ->where(
                                            'is_active',
                                            true
                                        )
                                        ->whereIn(
                                            'name',
                                            [
                                                'manager',
                                                'waiter',
                                                'chef',
                                            ]
                                        );

                                }
                            );

                    }
                )
                ->get()
                ->map(
                    function (Employee $employee) {

                        $roleName =
                            $employee
                                ->user
                                ?->role
                                ?->name;

                        return [

                            'id' =>
                                (int) $employee->id,

                            'staff_name' =>
                                $employee
                                    ->user
                                    ?->name
                                ??
                                'Unknown Staff',

                            'username' =>
                                $employee
                                    ->user
                                    ?->username,

                            'phone' =>
                                $employee->phone,

                            'email' =>
                                $employee
                                    ->user
                                    ?->email,

                            'role_id' =>
                                $employee
                                    ->user
                                    ?->role_id
                                        ? (int)
                                            $employee
                                                ->user
                                                ->role_id
                                        : null,

                            'role_name' =>
                                $roleName,

                            'role_label' =>
                                $roleName
                                    ? ucfirst($roleName)
                                    : 'No Role',

                        ];

                    }
                )
                ->sortBy('staff_name')
                ->values();

        return response()->json([

            'success' =>
                true,

            'message' =>
                'Available employees loaded successfully.',

            'data' =>
                $employees,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Schedule
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreShiftScheduleRequest $request
    ) {
        $schedule =
            $this
                ->shiftScheduleService
                ->createSchedule(
                    $request->validated(),
                    $request->user()
                );

        return (
            new ShiftScheduleResource(
                $schedule
            )
        )
            ->additional([

                'success' =>
                    true,

                'message' =>
                    'Recurring shift schedule created successfully.',

            ])
            ->response()
            ->setStatusCode(201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Schedule
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        ShiftSchedule $shiftSchedule
    ) {
        $scheduleDate =
            $request->input(
                'schedule_date',
                now()->format('Y-m-d')
            );

        $shiftSchedule->loadMissing([

            'employee.user.role',

            'overrides' =>
                function ($query) use ($scheduleDate) {

                    $query->whereDate(
                        'override_date',
                        $scheduleDate
                    );

                },

            'creator',

            'updater',

        ]);

        return (
            new ShiftScheduleResource(
                $shiftSchedule
            )
        )->additional([

            'success' =>
                true,

            'message' =>
                'Shift schedule loaded successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Schedule
    |--------------------------------------------------------------------------
    */

    public function update(
        UpdateShiftScheduleRequest $request,
        ShiftSchedule $shiftSchedule
    ) {
        $schedule =
            $this
                ->shiftScheduleService
                ->updateSchedule(
                    $shiftSchedule,
                    $request->validated(),
                    $request->user()
                );

        return (
            new ShiftScheduleResource(
                $schedule
            )
        )->additional([

            'success' =>
                true,

            'message' =>
                'Recurring shift schedule updated successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        Request $request,
        ShiftSchedule $shiftSchedule
    ) {
        $validated =
            $request->validate([

                'is_active' => [
                    'required',
                    'boolean',
                ],

            ]);

        $schedule =
            $this
                ->shiftScheduleService
                ->updateStatus(
                    $shiftSchedule,
                    (bool) $validated['is_active'],
                    $request->user()
                );

        return (
            new ShiftScheduleResource(
                $schedule
            )
        )->additional([

            'success' =>
                true,

            'message' =>
                $validated['is_active']
                    ? 'Shift schedule activated successfully.'
                    : 'Shift schedule deactivated successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        ShiftSchedule $shiftSchedule
    ) {
        $this
            ->shiftScheduleService
            ->deleteSchedule(
                $shiftSchedule,
                $request->user()
            );

        return response()->json([

            'success' =>
                true,

            'message' =>
                'Shift schedule deleted successfully.',

        ]);
    }
}