<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryPayrollResource extends JsonResource
{

    public function toArray(
        Request $request
    ): array {


        $shift =
            $this->employee
                ?->shiftSchedules
                ?->first();



        return [

            /*
            |--------------------------------------------------------------------------
            | Basic
            |--------------------------------------------------------------------------
            */


            'id' =>
                (int) $this->id,


            'employee_id' =>
                (int) $this->employee_id,


            'employee_name' =>
                $this->employee_name
                ??
                'Unknown Staff',



            'employee_initial' =>
                strtoupper(
                    substr(
                        $this->employee_name
                        ??
                        'U',
                        0,
                        1
                    )
                ),



            'employee_phone' =>
                $this->employee_phone,



            'employee_email' =>
                $this->employee_email,




            /*
            |--------------------------------------------------------------------------
            | Shift
            |--------------------------------------------------------------------------
            */


            'shift_time' =>
                $this->shiftTimeLabel(),



            'shift_start_time' =>
                $shift?->start_time,



            'shift_end_time' =>
                $shift?->end_time,




            /*
            |--------------------------------------------------------------------------
            | Salary Period
            |--------------------------------------------------------------------------
            */


            'period_start' =>
                $this->period_start
                    ?->format('Y-m-d'),



            'period_end' =>
                $this->period_end
                    ?->format('Y-m-d'),



            'period_label' =>
                $this->periodLabel(),

            /*
|--------------------------------------------------------------------------
| Attendance Summary
|--------------------------------------------------------------------------
*/

'worked_minutes' =>
    (int) (
        $this->worked_minutes
        ??
        0
    ),

'worked_duration_label' =>
    $this->durationLabel(
        (int) (
            $this->worked_minutes
            ??
            0
        )
    ),

'scheduled_minutes' =>
    (int) (
        $this->scheduled_minutes
        ??
        0
    ),

'scheduled_duration_label' =>
    $this->durationLabel(
        (int) (
            $this->scheduled_minutes
            ??
            0
        )
    ),

'break_minutes' =>
    (int) (
        $this->break_minutes
        ??
        0
    ),

'break_duration_label' =>
    $this->durationLabel(
        (int) (
            $this->break_minutes
            ??
            0
        )
    ),

'overtime_minutes' =>
    (int) (
        $this->overtime_minutes
        ??
        0
    ),

'overtime_duration_label' =>
    $this->durationLabel(
        (int) (
            $this->overtime_minutes
            ??
            0
        )
    ),

'late_minutes' =>
    (int) (
        $this->late_minutes
        ??
        0
    ),

'late_duration_label' =>
    $this->durationLabel(
        (int) (
            $this->late_minutes
            ??
            0
        )
    ),

'working_days' =>
    (int) (
        $this->salary_details_count
        ??
        $this->salaryDetails()->count()
    ),



            /*
            |--------------------------------------------------------------------------
            | Salary Amount
            |--------------------------------------------------------------------------
            */


            'hourly_rate' =>
                (float)
                $this->hourly_rate,



            'hourly_rate_formatted' =>
                $this->money(
                    $this->hourly_rate
                ),



            'regular_salary' =>
                (float)
                $this->regular_salary,



            'regular_salary_formatted' =>
                $this->money(
                    $this->regular_salary
                ),




            'overtime_salary' =>
                (float)
                $this->overtime_salary,



            'overtime_salary_formatted' =>
                $this->money(
                    $this->overtime_salary
                ),




            'adjustment_amount' =>
                (float)
                $this->adjustment_amount,



            'adjustment_amount_formatted' =>
                $this->signedMoney(
                    $this->adjustment_amount
                ),




            'total_amount' =>
                (float)
                $this->total_amount,



            'total_amount_formatted' =>
                $this->money(
                    $this->total_amount
                ),





            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */


            'payment_status' =>
                $this->payment_status,



            'payment_status_label' =>
                $this->paymentStatusLabel(),



            'status_badge' =>
                $this->payment_status === 'paid'
                    ? 'success'
                    : 'warning',




            /*
            |--------------------------------------------------------------------------
            | Payment Info
            |--------------------------------------------------------------------------
            */


            'paid_at' =>
                $this->paid_at
                    ?->toISOString(),



            'paid_by_name' =>
                $this->payer
                    ?->name,




            /*
            |--------------------------------------------------------------------------
            | Details
            |--------------------------------------------------------------------------
            */


            'details_count' =>
                $this->salary_details_count
                ??
                $this->salaryDetails()
                    ->count(),



            'can_edit' =>
    $this->exists
    &&
    ! $this->isPaid(),

'can_delete' =>
    $this->exists
    &&
    ! $this->isPaid(),
    'details_count' =>
    $this->salary_details_count
    ??
    (
        $this->relationLoaded(
            'salaryDetails'
        )
            ? $this->salaryDetails->count()
            : $this->salaryDetails()->count()
    ),




            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */


            'created_at' =>
                $this->created_at
                    ?->toISOString(),



            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),


        ];

    }





    /*
    |--------------------------------------------------------------------------
    | Period Label
    |--------------------------------------------------------------------------
    */


    private function periodLabel(): string
    {

        if(
            ! $this->period_start
            ||
            ! $this->period_end
        ){

            return '-';

        }



        return $this->period_start
            ->format('d M Y')
            .
            ' — '
            .
            $this->period_end
                ->format('d M Y');

    }







    /*
    |--------------------------------------------------------------------------
    | Shift Label
    |--------------------------------------------------------------------------
    */


    private function shiftTimeLabel(): string
    {

        $shift =
            $this->employee
                ?->shiftSchedules
                ?->first();



        if(
            ! $shift
        ){

            return '-';

        }



        return date(
            'h:i A',
            strtotime(
                $shift->start_time
            )
        )
        .
        ' - '
        .
        date(
            'h:i A',
            strtotime(
                $shift->end_time
            )
        );

    }








    /*
    |--------------------------------------------------------------------------
    | Money Format
    |--------------------------------------------------------------------------
    */


    private function money(
        mixed $amount
    ): string {


        return '৳ '
            .
            number_format(
                (float)$amount,
                2
            );


    }



    private function durationLabel(
    mixed $minutes
): string {

    $totalMinutes =
        max(
            0,
            (int) $minutes
        );

    $hours =
        intdiv(
            $totalMinutes,
            60
        );

    $remainingMinutes =
        $totalMinutes % 60;

    if (
        $hours > 0
        &&
        $remainingMinutes > 0
    ) {
        return "{$hours}h {$remainingMinutes}m";
    }

    if ($hours > 0) {
        return "{$hours}h";
    }

    return "{$remainingMinutes}m";
}




    private function signedMoney(
        mixed $amount
    ): string {


        $value =
            (float)$amount;



        if($value > 0){

            return '+৳ '
                .
                number_format(
                    $value,
                    2
                );

        }



        if($value < 0){

            return '-৳ '
                .
                number_format(
                    abs($value),
                    2
                );

        }



        return '৳ 0.00';


    }


}