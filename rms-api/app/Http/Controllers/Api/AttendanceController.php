<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Attendance List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $validated =
            $request->validate([

                'attendance_date' => [
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

                    Rule::in(
                        Attendance::allowedStatuses()
                    ),
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


        $attendanceDate =
            Carbon::parse(
                $validated['attendance_date']
                ??
                now()->format('Y-m-d')
            );


        /*
        |--------------------------------------------------------------------------
        | Sync Schedule with Attendance
        |--------------------------------------------------------------------------
        */

        $syncResult =
            $this->attendanceService
                ->syncForDate(
                    $attendanceDate,
                    $request->user()
                );


        /*
        |--------------------------------------------------------------------------
        | Base Daily Query
        |--------------------------------------------------------------------------
        |
        | Summary filters দিয়ে পরিবর্তিত হবে না।
        |
        */

        $baseQuery =
            Attendance::query()
                ->whereDate(
                    'attendance_date',
                    $attendanceDate
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
                );


        /*
        |--------------------------------------------------------------------------
        | Whole-Day Summary
        |--------------------------------------------------------------------------
        */

        $summary = [

            'total' =>
                (clone $baseQuery)
                    ->count(),

            'scheduled' =>
                (clone $baseQuery)
                    ->where(
                        'status',
                        Attendance::STATUS_SCHEDULED
                    )
                    ->count(),

            'absent' =>
                (clone $baseQuery)
                    ->where(
                        'status',
                        Attendance::STATUS_ABSENT
                    )
                    ->count(),

            'present' =>
                (clone $baseQuery)
                    ->where(
                        'status',
                        Attendance::STATUS_PRESENT
                    )
                    ->count(),

            'on_break' =>
                (clone $baseQuery)
                    ->where(
                        'status',
                        Attendance::STATUS_BREAK
                    )
                    ->count(),

            'completed' =>
                (clone $baseQuery)
                    ->where(
                        'status',
                        Attendance::STATUS_COMPLETED
                    )
                    ->count(),

            'leave' =>
                (clone $baseQuery)
                    ->where(
                        'status',
                        Attendance::STATUS_LEAVE
                    )
                    ->count(),

            'worked_minutes' =>
                (int) (
                    (clone $baseQuery)
                        ->sum(
                            'worked_minutes'
                        )
                ),

            'break_minutes' =>
                (int) (
                    (clone $baseQuery)
                        ->sum(
                            'break_minutes'
                        )
                ),

            'auto_checked_out' =>
                (clone $baseQuery)
                    ->where(
                        'auto_checked_out',
                        true
                    )
                    ->count(),

        ];


        /*
        |--------------------------------------------------------------------------
        | Filtered List Query
        |--------------------------------------------------------------------------
        */

        $query =
            (clone $baseQuery)
                ->with([

                    'employee.user.role',

                    'shiftSchedule',

                    'shiftScheduleOverride',

                    'breaks' =>
                        fn ($breakQuery) =>
                            $breakQuery
                                ->orderBy(
                                    'break_start_at'
                                ),

                ]);


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
        | Status Filter
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
                'status',
                $validated['status']
            );

        }


        $attendances =
            $query
                ->orderBy(
                    'scheduled_start_at'
                )
                ->paginate(
                    (int) (
                        $validated['per_page']
                        ??
                        10
                    )
                )
                ->withQueryString();


        return AttendanceResource::collection(
            $attendances
        )->additional([

            'success' =>
                true,

            'message' =>
                'Attendance records loaded successfully.',

            'selected_date' =>
                $attendanceDate
                    ->format('Y-m-d'),

            'selected_day' =>
                $attendanceDate
                    ->format('l'),

            'is_future_date' =>
                $attendanceDate
                    ->copy()
                    ->startOfDay()
                    ->gt(
                        today()
                    ),

            'summary' =>
                $summary,

            'sync' =>
                $syncResult,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Attendance
    |--------------------------------------------------------------------------
    */

    public function show(
        Attendance $attendance
    ) {
        $attendance->loadMissing([

            'employee.user.role',

            'shiftSchedule',

            'shiftScheduleOverride',

            'breaks' =>
                fn ($query) =>
                    $query->orderBy(
                        'break_start_at'
                    ),

        ]);


        return (
            new AttendanceResource(
                $attendance
            )
        )->additional([

            'success' =>
                true,

            'message' =>
                'Attendance record loaded successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Manual Synchronization
    |--------------------------------------------------------------------------
    */

    public function sync(Request $request)
    {
        $validated =
            $request->validate([

                'attendance_date' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                ],

            ]);


        $attendanceDate =
            $validated['attendance_date']
            ??
            now()->format('Y-m-d');


        $result =
            $this->attendanceService
                ->syncForDate(
                    $attendanceDate,
                    $request->user()
                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Attendance synchronization completed successfully.',

            'data' =>
                $result,

        ]);
    }
}