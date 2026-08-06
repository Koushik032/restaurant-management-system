<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftScheduleOverrideResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        return [

            'id' =>
                (int) $this->id,

            'shift_schedule_id' =>
                (int) $this->shift_schedule_id,

            'override_date' =>
                $this->override_date
                    ?->format('Y-m-d'),

            'override_date_label' =>
                $this->override_date
                    ?->format('d M Y'),

            'day_name' =>
                $this->override_date
                    ?->format('l'),

            'override_type' =>
                $this->override_type,

            'override_type_label' =>
                $this->override_type === 'day_off'
                    ? 'Day Off'
                    : 'Modified Shift',

            'start_time' =>
                $this->normalizeTime(
                    $this->start_time
                ),

            'start_time_label' =>
                $this->formatTime(
                    $this->start_time
                ),

            'end_time' =>
                $this->normalizeTime(
                    $this->end_time
                ),

            'end_time_label' =>
                $this->formatTime(
                    $this->end_time
                ),

            'grace_minutes' =>
                $this->grace_minutes !== null
                    ? (int) $this->grace_minutes
                    : null,

            'notes' =>
                $this->notes,

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),

        ];
    }


    private function normalizeTime(
        mixed $time
    ): ?string {

        if (! $time) {
            return null;
        }

        return Carbon::parse(
            (string) $time
        )->format('H:i');
    }


    private function formatTime(
        mixed $time
    ): ?string {

        if (! $time) {
            return null;
        }

        return Carbon::parse(
            (string) $time
        )->format('h:i A');
    }
}