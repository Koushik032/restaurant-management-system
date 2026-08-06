<?php

namespace App\Http\Resources;

use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantTableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Merge master ID
        |--------------------------------------------------------------------------
        */

        $mergeMasterId =
            $this->merged_with_id !== null
                ? (int) $this->merged_with_id
                : (int) $this->id;

        /*
        |--------------------------------------------------------------------------
        | Complete merge group
        |--------------------------------------------------------------------------
        |
        | Current table child হলে master এবং তার সব child table পাওয়া যাবে।
        | Current table master হলে master এবং সব child পাওয়া যাবে।
        |--------------------------------------------------------------------------
        */

        $mergeGroup =
            RestaurantTable::query()
                ->where(
                    function (
                        $query
                    ) use (
                        $mergeMasterId
                    ): void {
                        $query
                            ->where(
                                'id',
                                $mergeMasterId
                            )
                            ->orWhere(
                                'merged_with_id',
                                $mergeMasterId
                            );
                    }
                )
                ->orderBy('id')
                ->get();

        $isMerged =
            $mergeGroup->count() > 1;

        /*
        |--------------------------------------------------------------------------
        | Merge group IDs
        |--------------------------------------------------------------------------
        */

        $mergeGroupIds =
            $isMerged
                ? $mergeGroup
                    ->pluck('id')
                    ->map(
                        fn ($id): int =>
                            (int) $id
                    )
                    ->values()
                    ->all()
                : [];

        /*
        |--------------------------------------------------------------------------
        | Other merged table IDs
        |--------------------------------------------------------------------------
        */

        $mergedWithIds =
            $isMerged
                ? $mergeGroup
                    ->pluck('id')
                    ->map(
                        fn ($id): int =>
                            (int) $id
                    )
                    ->reject(
                        fn (
                            int $id
                        ): bool =>
                            $id ===
                            (int) $this->id
                    )
                    ->values()
                    ->all()
                : [];

        /*
        |--------------------------------------------------------------------------
        | Merge group table details
        |--------------------------------------------------------------------------
        */

        $mergeGroupTables =
            $isMerged
                ? $mergeGroup
                    ->map(
                        fn (
                            RestaurantTable $table
                        ): array => [
                            'id' =>
                                (int) $table->id,

                            'table_name' =>
                                $table->table_name,

                            'capacity' =>
                                (int) $table->capacity,

                            'section' =>
                                $table->section,

                            'section_label' =>
                                $this->formatSectionLabel(
                                    $table->section
                                ),

                            'status' =>
                                $table->status,

                            'status_label' =>
                                $this->formatStatusLabel(
                                    $table->status
                                ),

                            'current_status' =>
                                $table->current_status,

                            'current_status_label' =>
                                $this->formatStatusLabel(
                                    $table->current_status
                                ),

                            'reservation_start_at' =>
                                $table
                                    ->reservation_start_at
                                    ?->toISOString(),

                            'reservation_end_at' =>
                                $table
                                    ->reservation_end_at
                                    ?->toISOString(),

                            'merged_with_id' =>
                                $table->merged_with_id !==
                                null
                                    ? (int) $table
                                        ->merged_with_id
                                    : null,

                            'is_master' =>
                                (int) $table->id ===
                                $mergeMasterId,
                        ]
                    )
                    ->values()
                    ->all()
                : [];

        /*
        |--------------------------------------------------------------------------
        | Reservation information
        |--------------------------------------------------------------------------
        */

        $hasReservation =
            $this->reservation_start_at !==
                null &&
            $this->reservation_end_at !==
                null;

        $hasActiveReservation =
            (bool)
                $this
                    ->has_active_reservation;

        $hasUpcomingReservation =
            (bool)
                $this
                    ->has_upcoming_reservation;

        $hasValidReservation =
            $hasActiveReservation ||
            $hasUpcomingReservation;

        $reservationType =
            $this->getReservationType();

        return [
            /*
            |--------------------------------------------------------------------------
            | Basic table information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,

            'table_id' =>
                (int) $this->id,

            'table_name' =>
                $this->table_name,

            'capacity' =>
                (int) $this->capacity,

            'section' =>
                $this->section,

            'section_label' =>
                $this->formatSectionLabel(
                    $this->section
                ),

            /*
            |--------------------------------------------------------------------------
            | Raw database status
            |--------------------------------------------------------------------------
            |
            | Future reservation save হলে raw status available থাকতে পারে।
            |--------------------------------------------------------------------------
            */

            'status' =>
                $this->status,

            'status_label' =>
                $this->formatStatusLabel(
                    $this->status
                ),

            /*
            |--------------------------------------------------------------------------
            | Effective current status
            |--------------------------------------------------------------------------
            |
            | Current reservation active হলে reserved আসবে।
            |--------------------------------------------------------------------------
            */

            'current_status' =>
                $this->current_status,

            'current_status_label' =>
                $this->formatStatusLabel(
                    $this->current_status
                ),

            /*
            |--------------------------------------------------------------------------
            | Reservation status
            |--------------------------------------------------------------------------
            */

            'has_reservation' =>
                $hasValidReservation,

            'has_reservation_schedule' =>
                $hasReservation,

            'has_active_reservation' =>
                $hasActiveReservation,

            'has_upcoming_reservation' =>
                $hasUpcomingReservation,

            'reservation_type' =>
                $reservationType,

            /*
            |--------------------------------------------------------------------------
            | Reservation date and time
            |--------------------------------------------------------------------------
            */

            'reservation_start_at' =>
                $this->reservation_start_at
                    ?->toISOString(),

            'reservation_end_at' =>
                $this->reservation_end_at
                    ?->toISOString(),

            'reservation_date' =>
                $this->reservation_start_at
                    ?->format('Y-m-d'),

            'reservation_start_time' =>
                $this->reservation_start_at
                    ?->format('H:i'),

            'reservation_end_time' =>
                $this->reservation_end_at
                    ?->format('H:i'),

            'reservation_display' =>
                $hasReservation
                    ? sprintf(
                        '%s, %s - %s',
                        $this
                            ->reservation_start_at
                            ->format(
                                'd M Y'
                            ),
                        $this
                            ->reservation_start_at
                            ->format(
                                'h:i A'
                            ),
                        $this
                            ->reservation_end_at
                            ->format(
                                'h:i A'
                            )
                    )
                    : null,

            /*
            |--------------------------------------------------------------------------
            | Reservation edit data
            |--------------------------------------------------------------------------
            |
            | Edit modal সরাসরি এগুলো ব্যবহার করবে।
            |--------------------------------------------------------------------------
            */

            'edit_status' =>
                $hasValidReservation
                    ? RestaurantTable::STATUS_RESERVED
                    : $this->status,

            'edit_reservation_date' =>
                $this->reservation_start_at
                    ?->format('Y-m-d'),

            'edit_reservation_start_time' =>
                $this->reservation_start_at
                    ?->format('H:i'),

            'edit_reservation_end_time' =>
                $this->reservation_end_at
                    ?->format('H:i'),

            /*
            |--------------------------------------------------------------------------
            | Direct parent information
            |--------------------------------------------------------------------------
            */

            'merged_with_id' =>
                $this->merged_with_id !==
                null
                    ? (int) $this
                        ->merged_with_id
                    : null,

            'merged_with' =>
                $this->whenLoaded(
                    'mergedWith',
                    function (): ?array {
                        if (
                            !$this->mergedWith
                        ) {
                            return null;
                        }

                        return [
                            'id' =>
                                (int) $this
                                    ->mergedWith
                                    ->id,

                            'table_name' =>
                                $this
                                    ->mergedWith
                                    ->table_name,

                            'capacity' =>
                                (int) $this
                                    ->mergedWith
                                    ->capacity,

                            'section' =>
                                $this
                                    ->mergedWith
                                    ->section,

                            'status' =>
                                $this
                                    ->mergedWith
                                    ->status,

                            'current_status' =>
                                $this
                                    ->mergedWith
                                    ->current_status,
                        ];
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Merge information
            |--------------------------------------------------------------------------
            */

            'is_merged' =>
                $isMerged,

            'is_merge_master' =>
                $isMerged &&
                $this->merged_with_id ===
                    null,

            'is_merge_child' =>
                $this->merged_with_id !==
                    null,

            'merge_master_id' =>
                $isMerged
                    ? $mergeMasterId
                    : null,

            'merge_group_ids' =>
                $mergeGroupIds,

            'merged_with_ids' =>
                $mergedWithIds,

            'merge_group_tables' =>
                $mergeGroupTables,

            'merged_table_count' =>
                $isMerged
                    ? $mergeGroup->count()
                    : 0,

            'merged_total_capacity' =>
                $isMerged
                    ? (int) $mergeGroup
                        ->sum('capacity')
                    : null,

            /*
            |--------------------------------------------------------------------------
            | Other information
            |--------------------------------------------------------------------------
            */

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

    /**
     * Format section label.
     */
    private function formatSectionLabel(
        ?string $section
    ): string {
        return match ($section) {
            RestaurantTable::SECTION_AC =>
                'AC',

            RestaurantTable::SECTION_NON_AC =>
                'Non-AC',

            RestaurantTable::SECTION_OUTDOOR =>
                'Outdoor',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        (string) $section
                    )
                ),
        };
    }

    /**
     * Format status label.
     */
    private function formatStatusLabel(
        ?string $status
    ): string {
        return match ($status) {
            RestaurantTable::STATUS_AVAILABLE =>
                'Available',

            RestaurantTable::STATUS_OCCUPIED =>
                'Occupied',

            RestaurantTable::STATUS_RESERVED =>
                'Reserved',

            RestaurantTable::STATUS_CLEANING =>
                'Cleaning',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        (string) $status
                    )
                ),
        };
    }

    /**
     * Determine reservation type.
     */
    private function getReservationType(): ?string
    {
        if (
            !$this->reservation_start_at ||
            !$this->reservation_end_at
        ) {
            return null;
        }

        if (
            $this->has_active_reservation
        ) {
            return 'active';
        }

        if (
            $this->has_upcoming_reservation
        ) {
            return 'upcoming';
        }

        return 'expired';
    }
}