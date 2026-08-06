<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'employee_id' => [
                'required',
                'integer',

                Rule::exists(
                    'employees',
                    'id'
                )->whereNull(
                    'deleted_at'
                ),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'working_days' => [
                'required',
                'array',
                'min:1',
                'max:7',
            ],

            'working_days.*' => [
                'required',
                'string',

                Rule::in(
                    $this->allowedDays()
                ),
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
                'different:start_time',
            ],

            'grace_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:180',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

        ];
    }


    public function messages(): array
    {
        return [

            'employee_id.required' =>
                'Please select an employee.',

            'employee_id.exists' =>
                'Selected employee is invalid or deleted.',

            'start_date.required' =>
                'Schedule start date is required.',

            'end_date.required' =>
                'Schedule end date is required.',

            'end_date.after_or_equal' =>
                'Schedule end date cannot be before the start date.',

            'working_days.required' =>
                'Please select at least one working day.',

            'working_days.min' =>
                'Please select at least one working day.',

            'working_days.*.in' =>
                'One or more selected working days are invalid.',

            'start_time.required' =>
                'Shift start time is required.',

            'start_time.date_format' =>
                'Start time must use the HH:MM format.',

            'end_time.required' =>
                'Shift end time is required.',

            'end_time.date_format' =>
                'End time must use the HH:MM format.',

            'end_time.different' =>
                'Shift start time and end time cannot be the same.',

            'grace_minutes.min' =>
                'Grace time cannot be negative.',

            'grace_minutes.max' =>
                'Grace time cannot exceed 180 minutes.',

        ];
    }


    protected function prepareForValidation(): void
    {
        $workingDays =
            is_array($this->working_days)
                ? array_values(
                    array_unique(
                        array_map(
                            fn ($day) =>
                                strtolower(
                                    trim(
                                        (string) $day
                                    )
                                ),
                            $this->working_days
                        )
                    )
                )
                : $this->working_days;

        $this->merge([

            'employee_id' =>
                $this->employee_id !== null
                    ? (int) $this->employee_id
                    : null,

            'working_days' =>
                $workingDays,

            'grace_minutes' =>
                $this->grace_minutes === ''
                ||
                $this->grace_minutes === null
                    ? 0
                    : (int) $this->grace_minutes,

            'is_active' =>
                $this->has('is_active')
                    ? filter_var(
                        $this->is_active,
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    )
                    : true,

            'notes' =>
                is_string($this->notes)
                    ? trim($this->notes)
                    : $this->notes,

        ]);
    }


    private function allowedDays(): array
    {
        return [

            'saturday',

            'sunday',

            'monday',

            'tuesday',

            'wednesday',

            'thursday',

            'friday',

        ];
    }
}