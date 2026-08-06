<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {
    }

    public function createEmployee(
        array $data,
        User $authUser
    ): Employee {

        return DB::transaction(
            function () use (
                $data,
                $authUser
            ) {

                /*
                |--------------------------------------------------------------------------
                | Create User Account
                |--------------------------------------------------------------------------
                */

                $user = User::create([

                    'role_id' =>
                        $data['role_id'],

                    'name' =>
                        $data['name'],

                    'username' =>
                        $data['username'],

                    'email' =>
                        $data['email'],

                    /*
                    | User model-এ password hashed cast থাকলেও
                    | explicit Hash ব্যবহার করলে behavior পরিষ্কার থাকে।
                    */

                    'password' =>
                        Hash::make(
                            $data['password']
                        ),

                    'is_active' =>
                        array_key_exists(
                            'is_active',
                            $data
                        )
                            ? (bool) $data['is_active']
                            : true,

                    'failed_login_attempts' =>
                        0,

                    'blocked_at' =>
                        (
                            array_key_exists(
                                'is_active',
                                $data
                            )
                            &&
                            ! (bool) $data['is_active']
                        )
                            ? now()
                            : null,

                ]);


                /*
                |--------------------------------------------------------------------------
                | Create Employee Profile
                |--------------------------------------------------------------------------
                */

                $employee = Employee::create([

                    'user_id' =>
                        $user->id,

                    'phone' =>
                        $data['phone']
                        ??
                        null,

                    'joining_date' =>
                        $data['joining_date'],

                    'hourly_rate' =>
                        $data['hourly_rate'],

                    'current_status' =>
    Employee::STATUS_NONE,

                    'status_updated_at' =>
                        now(),

                    'created_by' =>
                        $authUser->id,

                    'updated_by' =>
                        $authUser->id,

                ]);


                return $employee->fresh([

                    'user.role',

                    'creator',

                    'updater',

                ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Employee
    |--------------------------------------------------------------------------
    |
    | User account এবং employee profile দুইটাই update হবে।
    |
    */

    public function updateEmployee(
        Employee $employee,
        array $data,
        User $authUser
    ): Employee {

        return DB::transaction(
            function () use (
                $employee,
                $data,
                $authUser
            ) {

                $employee->loadMissing(
                    'user'
                );


                if (! $employee->user) {

                    throw ValidationException::withMessages([

                        'employee' =>
                            'The employee account could not be found.',

                    ]);

                }


                /*
                |--------------------------------------------------------------------------
                | Update User Account
                |--------------------------------------------------------------------------
                */

                $userData = [];


                if (
                    array_key_exists(
                        'role_id',
                        $data
                    )
                ) {

                    $userData['role_id'] =
                        $data['role_id'];

                }


                if (
                    array_key_exists(
                        'name',
                        $data
                    )
                ) {

                    $userData['name'] =
                        $data['name'];

                }


                if (
                    array_key_exists(
                        'username',
                        $data
                    )
                ) {

                    $userData['username'] =
                        $data['username'];

                }


                if (
                    array_key_exists(
                        'email',
                        $data
                    )
                ) {

                    $userData['email'] =
                        $data['email'];

                }


                /*
                |--------------------------------------------------------------------------
                | Optional Password Update
                |--------------------------------------------------------------------------
                */

                if (
                    ! empty(
                        $data['password']
                        ??
                        null
                    )
                ) {

                    $userData['password'] =
                        Hash::make(
                            $data['password']
                        );

                }


                /*
                |--------------------------------------------------------------------------
                | Account Active / Block Status
                |--------------------------------------------------------------------------
                */

                if (
                    array_key_exists(
                        'is_active',
                        $data
                    )
                ) {

                    $isActive =
                        (bool) $data['is_active'];


                    $userData['is_active'] =
                        $isActive;


                    $userData['blocked_at'] =
                        $isActive
                            ? null
                            : now();


                    if ($isActive) {

                        $userData['failed_login_attempts'] =
                            0;

                    }

                }


                if (
                    count(
                        $userData
                    ) > 0
                ) {

                    $employee->user->update(
                        $userData
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Update Employee Profile
                |--------------------------------------------------------------------------
                */

                $employeeData = [];


                if (
                    array_key_exists(
                        'phone',
                        $data
                    )
                ) {

                    $employeeData['phone'] =
                        $data['phone'];

                }


                if (
                    array_key_exists(
                        'joining_date',
                        $data
                    )
                ) {

                    $employeeData['joining_date'] =
                        $data['joining_date'];

                }


                if (
                    array_key_exists(
                        'hourly_rate',
                        $data
                    )
                ) {

                    $employeeData['hourly_rate'] =
                        $data['hourly_rate'];

                }

                $employeeData['updated_by'] =
                    $authUser->id;


                $employee->update(
                    $employeeData
                );


                return $employee->fresh([

                    'user.role',

                    'creator',

                    'updater',

                ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Current Status
    |--------------------------------------------------------------------------
    |
    | Employee list থেকে inline status update করার জন্য।
    |
    | Attendance module তৈরি হওয়ার পর এখান থেকেই:
    |
    | present → check-in
    | break   → break start
    | present → break end
    | none    → check-out
    |
    | workflow connect করা হবে।
    |
    */

    /*
|--------------------------------------------------------------------------
| Update Current Status with Attendance
|--------------------------------------------------------------------------
*/

public function updateCurrentStatus(
    Employee $employee,
    string $status,
    User $authUser
): Employee {

    if (
        ! in_array(
            $status,
            Employee::allowedStatuses(),
            true
        )
    ) {
        throw ValidationException::withMessages([

            'current_status' =>
                'The selected employee status is invalid.',

        ]);
    }


    $this->attendanceService
        ->handleEmployeeStatusChange(
            $employee,
            $status,
            $authUser
        );


    return $employee->fresh([

        'user.role',

        'creator',

        'updater',

    ]);
}


    /*
    |--------------------------------------------------------------------------
    | Update Account Status
    |--------------------------------------------------------------------------
    |
    | Block করলে:
    |
    | users.is_active = false
    | users.blocked_at = current time
    |
    | Unblock করলে:
    |
    | users.is_active = true
    | users.blocked_at = null
    |
    */

    public function updateAccountStatus(
        Employee $employee,
        bool $isActive,
        User $authUser
    ): Employee {

        return DB::transaction(
            function () use (
                $employee,
                $isActive,
                $authUser
            ) {

                $employee->loadMissing(
                    'user'
                );


                if (! $employee->user) {

                    throw ValidationException::withMessages([

                        'employee' =>
                            'The employee account could not be found.',

                    ]);

                }


                $employee->user->update([

                    'is_active' =>
                        $isActive,

                    'blocked_at' =>
                        $isActive
                            ? null
                            : now(),

                    'failed_login_attempts' =>
                        $isActive
                            ? 0
                            : $employee->user
                                ->failed_login_attempts,

                ]);


                $employee->update([

                    'updated_by' =>
                        $authUser->id,

                ]);


                return $employee->fresh([

                    'user.role',

                    'creator',

                    'updater',

                ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Soft Delete Employee
    |--------------------------------------------------------------------------
    |
    | Employee profile soft delete হবে।
    | User account block হবে, কিন্তু hard delete হবে না।
    |
    */

    public function deleteEmployee(
        Employee $employee,
        User $authUser
    ): void {

        DB::transaction(
            function () use (
                $employee,
                $authUser
            ) {

                $employee->loadMissing(
                    'user'
                );


                if ($employee->user) {

                    $employee->user->update([

                        'is_active' =>
                            false,

                        'blocked_at' =>
                            now(),

                    ]);

                }


                $employee->update([

                    'current_status' =>
                        Employee::STATUS_NONE,

                    'status_updated_at' =>
                        now(),

                    'updated_by' =>
                        $authUser->id,

                ]);


                $employee->delete();

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restore Employee
    |--------------------------------------------------------------------------
    |
    | ভবিষ্যৎ restore feature-এর জন্য ready রাখা হয়েছে।
    |
    */

    public function restoreEmployee(
        Employee $employee,
        User $authUser
    ): Employee {

        return DB::transaction(
            function () use (
                $employee,
                $authUser
            ) {

                if (! $employee->trashed()) {

                    throw ValidationException::withMessages([

                        'employee' =>
                            'This employee is not deleted.',

                    ]);

                }


                $employee->restore();


                $employee->loadMissing(
                    'user'
                );


                if ($employee->user) {

                    $employee->user->update([

                        'is_active' =>
                            true,

                        'blocked_at' =>
                            null,

                        'failed_login_attempts' =>
                            0,

                    ]);

                }


                $employee->update([

                    'current_status' =>
                        Employee::STATUS_NONE,

                    'status_updated_at' =>
                        now(),

                    'updated_by' =>
                        $authUser->id,

                ]);


                return $employee->fresh([

                    'user.role',

                    'creator',

                    'updater',

                ]);

            }
        );
    }
}