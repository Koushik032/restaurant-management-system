<?php

namespace App\Http\Requests\Api;

use App\Models\ShiftScheduleOverride;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftScheduleOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [

            'override_date' => [
                'sometimes',
                'required',
                'date',
            ],

            'override_type' => [
                'sometimes',
                'required',
                'string',

                Rule::in(
                    ShiftScheduleOverride::allowedTypes()
                ),
            ],

            'start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'end_time' => [
                'nullable',
                'date_format:H:i',
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


    protected function prepareForValidation(): void
    {
        $prepared = [];


        if ($this->has('override_type')) {

            $prepared['override_type'] =
                is_string($this->override_type)
                    ? strtolower(
                        trim(
                            $this->override_type
                        )
                    )
                    : $this->override_type;

        }


        if ($this->has('grace_minutes')) {

            $prepared['grace_minutes'] =
                $this->grace_minutes === ''
                ||
                $this->grace_minutes === null
                    ? null
                    : (int) $this->grace_minutes;

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
}