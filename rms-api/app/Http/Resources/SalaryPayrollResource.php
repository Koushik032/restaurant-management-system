<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryPayrollResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        return [

            'id' =>
                (int) $this->id,

            'employee_id' =>
                (int) $this->employee_id,

            'employee_name' =>
                $this->employee_name,

            'employee_phone' =>
                $this->employee_phone,

            'employee_email' =>
                $this->employee_email,

            'period_start' =>
                $this->period_start
                    ?->format('Y-m-d'),

            'period_start_label' =>
                $this->period_start
                    ?->format('d M Y'),

            'period_end' =>
                $this->period_end
                    ?->format('Y-m-d'),

            'period_end_label' =>
                $this->period_end
                    ?->format('d M Y'),

            'period_label' =>
                $this->periodLabel(),

            'hourly_rate' =>
                (float) $this->hourly_rate,

            'hourly_rate_formatted' =>
                $this->money(
                    $this->hourly_rate
                )
                .
                ' / hour',

            'regular_salary' =>
                (float) $this->regular_salary,

            'regular_salary_formatted' =>
                $this->money(
                    $this->regular_salary
                ),

            'overtime_salary' =>
                (float) $this->overtime_salary,

            'overtime_salary_formatted' =>
                $this->money(
                    $this->overtime_salary
                ),

            'adjustment_amount' =>
                (float) $this->adjustment_amount,

            'adjustment_amount_formatted' =>
                $this->signedMoney(
                    $this->adjustment_amount
                ),

            'total_amount' =>
                (float) $this->total_amount,

            'total_amount_formatted' =>
                $this->money(
                    $this->total_amount
                ),

            'payment_status' =>
                $this->payment_status,

            'payment_status_label' =>
                $this->paymentStatusLabel(),

            'paid_at' =>
                $this->paid_at
                    ?->toISOString(),

            'paid_at_label' =>
                $this->paid_at
                    ?->format(
                        'd M Y, h:i A'
                    ),

            'paid_by_name' =>
                $this->payer
                    ?->name,

            'notes' =>
                $this->notes,

            'details_count' =>
                $this->salary_details_count
                ??
                $this->salaryDetails()
                    ->count(),

            'can_edit' =>
                ! $this->isPaid(),

            'can_delete' =>
                ! $this->isPaid(),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),

        ];
    }


    private function periodLabel(): string
    {
        if (
            ! $this->period_start
            ||
            ! $this->period_end
        ) {
            return '-';
        }

        if (
            $this->period_start
                ->isSameDay(
                    $this->period_end
                )
        ) {
            return $this->period_start
                ->format('d M Y');
        }

        return $this->period_start
            ->format('d M Y')
            .
            ' — '
            .
            $this->period_end
                ->format('d M Y');
    }


    private function money(
        mixed $amount
    ): string {

        return '৳ '
            .
            number_format(
                (float) $amount,
                2
            );
    }


    private function signedMoney(
        mixed $amount
    ): string {

        $value =
            (float) $amount;

        if ($value > 0) {

            return '+৳ '
                .
                number_format(
                    $value,
                    2
                );

        }

        if ($value < 0) {

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