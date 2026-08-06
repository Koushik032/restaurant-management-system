<?php

namespace App\Http\Requests\Api;

use App\Models\ShiftScheduleOverride;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftScheduleOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'override_date' => [
                'required',
                'date',
            ],

            'override_type' => [
                'required',
                'string',

                Rule::in(
                    ShiftScheduleOverride::allowedTypes()
                ),
            ],

            'start_time' => [
                'nullable',
                'required_if:override_type,modified',
                'date_format:H:i',
            ],

            'end_time' => [
                'nullable',
                'required_if:override_type,modified',
                'date_format:H:i',
                'different:start_time',
            ],

            'grace_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:180',
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

            'override_date.required' =>
                'Override date is required.',

            'override_type.required' =>
                'Please select an override type.',

            'override_type.in' =>
                'Selected override type is invalid.',

            'start_time.required_if' =>
                'Modified start time is required.',

            'end_time.required_if' =>
                'Modified end time is required.',

            'end_time.different' =>
                'Modified start and end time cannot be the same.',

        ];
    }


    protected function prepareForValidation(): void
    {
        $this->merge([

            'override_type' =>
                is_string($this->override_type)
                    ? strtolower(
                        trim(
                            $this->override_type
                        )
                    )
                    : $this->override_type,

            'grace_minutes' =>
                $this->grace_minutes === ''
                ||
                $this->grace_minutes === null
                    ? null
                    : (int) $this->grace_minutes,

            'notes' =>
                is_string($this->notes)
                    ? trim($this->notes)
                    : $this->notes,

        ]);
    }
}