<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'employee_id' => [
                'sometimes',
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
                'sometimes',
                'required',
                'date',
            ],

            'end_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'working_days' => [
                'sometimes',
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
                'sometimes',
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'sometimes',
                'required',
                'date_format:H:i',
            ],

            'grace_minutes' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:180',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

        ];
    }


    protected function prepareForValidation(): void
    {
        $prepared = [];


        if ($this->has('employee_id')) {

            $prepared['employee_id'] =
                (int) $this->employee_id;

        }


        if (
            $this->has('working_days')
            &&
            is_array($this->working_days)
        ) {

            $prepared['working_days'] =
                array_values(
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
                );

        }


        if ($this->has('grace_minutes')) {

            $prepared['grace_minutes'] =
                $this->grace_minutes === ''
                ||
                $this->grace_minutes === null
                    ? 0
                    : (int) $this->grace_minutes;

        }


        if ($this->has('is_active')) {

            $prepared['is_active'] =
                filter_var(
                    $this->is_active,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );

        }


        if ($this->has('notes')) {

            $prepared['notes'] =
                is_string($this->notes)
                    ? trim($this->notes)
                    : $this->notes;

        }


        if (count($prepared) > 0) {

            $this->merge(
                $prepared
            );

        }
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