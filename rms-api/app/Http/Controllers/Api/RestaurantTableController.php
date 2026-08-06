<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantTableResource;
use App\Models\RestaurantTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    /**
     * Display restaurant tables.
     */
    public function index(
        Request $request
    ): JsonResponse {
        $perPage = (int) $request->input(
            'per_page',
            100
        );

        $perPage = max(
            5,
            min($perPage, 100)
        );

        $now = now();

        $query = RestaurantTable::query()
            ->with([
                'mergedWith',
                'mergedTables',
            ])
            ->latest('id');

        /*
        |--------------------------------------------------------------------------
        | Search filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input(
                    'search'
                )
            );

            $query->where(
                function (
                    Builder $builder
                ) use (
                    $search
                ): void {
                    $builder
                        ->where(
                            'table_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'notes',
                            'like',
                            "%{$search}%"
                        );

                    if (is_numeric($search)) {
                        $builder->orWhere(
                            'id',
                            (int) $search
                        );
                    }
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Section filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('section')) {
            $query->where(
                'section',
                $request->input('section')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Capacity filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('capacity')) {
            $query->where(
                'capacity',
                '>=',
                (int) $request->input(
                    'capacity'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Effective status filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $status = (string) $request->input(
                'status'
            );

            if (
                $status ===
                RestaurantTable::STATUS_RESERVED
            ) {
                /*
                |--------------------------------------------------------------------------
                | Reserved section
                |--------------------------------------------------------------------------
                |
                | Show:
                | - Active reservations
                | - Upcoming reservations
                |
                | Hide expired reservations.
                */

                $query
                    ->whereNotNull(
                        'reservation_start_at'
                    )
                    ->whereNotNull(
                        'reservation_end_at'
                    )
                    ->where(
                        'reservation_end_at',
                        '>',
                        $now
                    );
            } elseif (
                $status ===
                RestaurantTable::STATUS_AVAILABLE
            ) {
                /*
                |--------------------------------------------------------------------------
                | Available now
                |--------------------------------------------------------------------------
                |
                | Future reservation does not block a table now.
                | Active reservation blocks it.
                */

                $query
                    ->where(
                        'status',
                        RestaurantTable::STATUS_AVAILABLE
                    )
                    ->where(
                        function (
                            Builder $builder
                        ) use (
                            $now
                        ): void {
                            $builder
                                ->whereNull(
                                    'reservation_start_at'
                                )
                                ->orWhereNull(
                                    'reservation_end_at'
                                )
                                ->orWhere(
                                    'reservation_start_at',
                                    '>',
                                    $now
                                )
                                ->orWhere(
                                    'reservation_end_at',
                                    '<=',
                                    $now
                                );
                        }
                    );
            } elseif (
                $status ===
                RestaurantTable::STATUS_OCCUPIED
            ) {
                $query->where(
                    'status',
                    RestaurantTable::STATUS_OCCUPIED
                );
            } elseif (
                $status ===
                RestaurantTable::STATUS_CLEANING
            ) {
                $query->where(
                    'status',
                    RestaurantTable::STATUS_CLEANING
                );
            }
        }

        $tables = $query->paginate(
            $perPage
        );

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summary = [
            'total' =>
                RestaurantTable::query()
                    ->count(),

            'available' =>
                $this->availableNowQuery(
                    $now
                )->count(),

            'occupied' =>
                RestaurantTable::query()
                    ->where(
                        'status',
                        RestaurantTable::STATUS_OCCUPIED
                    )
                    ->count(),

            /*
            |--------------------------------------------------------------------------
            | Reserved count means active + upcoming reservations
            |--------------------------------------------------------------------------
            */

            'reserved' =>
                RestaurantTable::query()
                    ->whereNotNull(
                        'reservation_start_at'
                    )
                    ->whereNotNull(
                        'reservation_end_at'
                    )
                    ->where(
                        'reservation_end_at',
                        '>',
                        $now
                    )
                    ->count(),

            'active_reserved' =>
                RestaurantTable::query()
                    ->whereNotNull(
                        'reservation_start_at'
                    )
                    ->whereNotNull(
                        'reservation_end_at'
                    )
                    ->where(
                        'reservation_start_at',
                        '<=',
                        $now
                    )
                    ->where(
                        'reservation_end_at',
                        '>',
                        $now
                    )
                    ->count(),

            'upcoming_reserved' =>
                RestaurantTable::query()
                    ->whereNotNull(
                        'reservation_start_at'
                    )
                    ->whereNotNull(
                        'reservation_end_at'
                    )
                    ->where(
                        'reservation_start_at',
                        '>',
                        $now
                    )
                    ->count(),

            'cleaning' =>
                RestaurantTable::query()
                    ->where(
                        'status',
                        RestaurantTable::STATUS_CLEANING
                    )
                    ->count(),
        ];

        return response()->json([
            'success' => true,

            'message' =>
                'Restaurant tables retrieved successfully.',

            'data' =>
                RestaurantTableResource::collection(
                    $tables->items()
                ),

            'summary' =>
                $summary,

            'meta' => [
                'current_page' =>
                    $tables->currentPage(),

                'last_page' =>
                    $tables->lastPage(),

                'per_page' =>
                    $tables->perPage(),

                'total' =>
                    $tables->total(),

                'from' =>
                    $tables->firstItem() ?? 0,

                'to' =>
                    $tables->lastItem() ?? 0,
            ],
        ]);
    }
    public function store(
    Request $request
): JsonResponse {
    $validated = $request->validate([
        'table_name' => [
            'required',
            'string',
            'max:100',
            Rule::unique(
                'restaurant_tables',
                'table_name'
            ),
        ],

        'capacity' => [
            'required',
            'integer',
            'min:1',
            'max:100',
        ],

        'section' => [
            'required',
            Rule::in([
                'ac',
                'non_ac',
                'outdoor',
            ]),
        ],
    ], [
        'table_name.required' =>
            'The table name is required.',

        'table_name.unique' =>
            'A table with this name already exists.',

        'capacity.required' =>
            'The table capacity is required.',

        'capacity.integer' =>
            'The capacity must be a whole number.',

        'capacity.min' =>
            'The capacity must be at least 1.',

        'capacity.max' =>
            'The capacity cannot exceed 100.',

        'section.required' =>
            'Please select a table section.',

        'section.in' =>
            'The selected table section is invalid.',
    ]);

    $restaurantTable =
        RestaurantTable::query()->create([
            'table_name' =>
                trim(
                    $validated[
                        'table_name'
                    ]
                ),

            'capacity' =>
                $validated[
                    'capacity'
                ],

            'section' =>
                $validated[
                    'section'
                ],

            /*
            |--------------------------------------------------------------------------
            | New table default state
            |--------------------------------------------------------------------------
            */

            'status' =>
                'available',

            'notes' =>
                null,

            'merged_with_id' =>
                null,
        ]);

    return response()->json([
        'success' => true,

        'message' =>
            'Restaurant table created successfully.',

        'data' =>
            $restaurantTable->fresh(),
    ], 201);
}

    /**
     * Display one restaurant table.
     */
    public function show(
        RestaurantTable $restaurantTable
    ): JsonResponse {
        $restaurantTable->load([
            'mergedWith',
            'mergedTables',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Restaurant table retrieved successfully.',

            'data' =>
                new RestaurantTableResource(
                    $restaurantTable
                ),
        ]);
    }

    /**
     * Update restaurant table.
     */
    public function update(
        Request $request,
        RestaurantTable $restaurantTable
    ): JsonResponse {
        $validated = $request->validate([
            'table_name' => [
                'required',
                'string',
                'max:100',
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'section' => [
                'required',
                'in:ac,non_ac,outdoor',
            ],

            'status' => [
                'required',
                'in:available,occupied,reserved,cleaning',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'reservation_start_at' => [
                'nullable',
                'date',
                'required_if:status,reserved',
            ],

            'reservation_end_at' => [
                'nullable',
                'date',
                'required_if:status,reserved',
                'after:reservation_start_at',
            ],

            'merge_table_ids' => [
                'nullable',
                'array',
            ],

            'merge_table_ids.*' => [
                'integer',
                'distinct',
                'exists:restaurant_tables,id',
            ],
        ]);

        $isReservation =
            $validated['status'] ===
            RestaurantTable::STATUS_RESERVED;

        /*
        |--------------------------------------------------------------------------
        | Parse reservation date and time
        |--------------------------------------------------------------------------
        */

        $reservationStartAt = null;
        $reservationEndAt = null;

        if ($isReservation) {
            $reservationStartAt =
                Carbon::parse(
                    $validated[
                        'reservation_start_at'
                    ]
                );

            $reservationEndAt =
                Carbon::parse(
                    $validated[
                        'reservation_end_at'
                    ]
                );

            if (
                $reservationEndAt
                    ->lessThanOrEqualTo(
                        now()
                    )
            ) {
                throw ValidationException::withMessages([
                    'reservation_end_at' => [
                        'Reservation end time must be in the future.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Merge selection
        |--------------------------------------------------------------------------
        */

        $requestedMergeTableIds = collect(
            $validated[
                'merge_table_ids'
            ] ?? []
        )
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->unique()
            ->values();

        $mergeAllowedStatuses = [
            RestaurantTable::STATUS_OCCUPIED,
            RestaurantTable::STATUS_RESERVED,
        ];

        if (
            $requestedMergeTableIds
                ->isNotEmpty() &&
            !in_array(
                $validated['status'],
                $mergeAllowedStatuses,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'merge_table_ids' => [
                    'Tables can only be merged when status is occupied or reserved.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Database base status
        |--------------------------------------------------------------------------
        |
        | Reservation is a schedule, not the physical status.
        |
        | When a reservation is saved:
        | - Existing occupied status stays occupied
        | - Existing cleaning status stays cleaning
        | - Otherwise base status is available
        */

        if ($isReservation) {
            if (
                in_array(
                    $restaurantTable->status,
                    [
                        RestaurantTable::STATUS_OCCUPIED,
                        RestaurantTable::STATUS_CLEANING,
                    ],
                    true
                )
            ) {
                $storedStatus =
                    $restaurantTable->status;
            } else {
                $storedStatus =
                    RestaurantTable::STATUS_AVAILABLE;
            }
        } else {
            $storedStatus =
                $validated['status'];
        }

        $result = DB::transaction(
            function () use (
                $validated,
                $restaurantTable,
                $isReservation,
                $reservationStartAt,
                $reservationEndAt,
                $storedStatus,
                $requestedMergeTableIds
            ): RestaurantTable {
                $currentTable =
                    RestaurantTable::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $restaurantTable->id
                        );

                $masterId =
                    $currentTable->merged_with_id
                        ? (int)
                            $currentTable
                                ->merged_with_id
                        : (int)
                            $currentTable->id;

                $currentGroup =
                    RestaurantTable::query()
                        ->where(
                            function (
                                Builder $query
                            ) use (
                                $masterId
                            ): void {
                                $query
                                    ->where(
                                        'id',
                                        $masterId
                                    )
                                    ->orWhere(
                                        'merged_with_id',
                                        $masterId
                                    );
                            }
                        )
                        ->lockForUpdate()
                        ->get();

                $currentGroupIds =
                    $currentGroup
                        ->pluck('id')
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        );

                /*
                |--------------------------------------------------------------------------
                | Only newly selected tables
                |--------------------------------------------------------------------------
                */

                $newMergeTableIds =
                    $requestedMergeTableIds
                        ->reject(
                            fn (
                                int $id
                            ): bool =>
                                $currentGroupIds
                                    ->contains($id)
                        )
                        ->values();

                if (
                    $newMergeTableIds
                        ->contains(
                            (int)
                                $currentTable->id
                        )
                ) {
                    throw ValidationException::withMessages([
                        'merge_table_ids' => [
                            'The current table cannot be selected again.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Validate newly selected tables
                |--------------------------------------------------------------------------
                */

                if (
                    $newMergeTableIds
                        ->isNotEmpty()
                ) {
                    $selectedTables =
                        RestaurantTable::query()
                            ->whereIn(
                                'id',
                                $newMergeTableIds
                            )
                            ->lockForUpdate()
                            ->get();

                    if (
                        $selectedTables
                            ->count() !==
                        $newMergeTableIds
                            ->count()
                    ) {
                        throw ValidationException::withMessages([
                            'merge_table_ids' => [
                                'One or more selected tables no longer exist.',
                            ],
                        ]);
                    }

                    foreach (
                        $selectedTables
                        as $selectedTable
                    ) {
                        if (
                            $selectedTable->status !==
                            RestaurantTable::STATUS_AVAILABLE
                        ) {
                            throw ValidationException::withMessages([
                                'merge_table_ids' => [
                                    "{$selectedTable->table_name} is not available.",
                                ],
                            ]);
                        }

                        if (
                            $selectedTable
                                ->merged_with_id !==
                            null
                        ) {
                            throw ValidationException::withMessages([
                                'merge_table_ids' => [
                                    "{$selectedTable->table_name} already belongs to another merge group.",
                                ],
                            ]);
                        }

                        $controlsAnotherGroup =
                            RestaurantTable::query()
                                ->where(
                                    'merged_with_id',
                                    $selectedTable->id
                                )
                                ->lockForUpdate()
                                ->exists();

                        if (
                            $controlsAnotherGroup
                        ) {
                            throw ValidationException::withMessages([
                                'merge_table_ids' => [
                                    "{$selectedTable->table_name} controls another merge group.",
                                ],
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Reservation conflict
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $isReservation &&
                            $selectedTable
                                ->reservation_start_at &&
                            $selectedTable
                                ->reservation_end_at
                        ) {
                            $hasConflict =
                                $selectedTable
                                    ->reservation_start_at
                                    ->lessThan(
                                        $reservationEndAt
                                    ) &&
                                $selectedTable
                                    ->reservation_end_at
                                    ->greaterThan(
                                        $reservationStartAt
                                    );

                            if ($hasConflict) {
                                throw ValidationException::withMessages([
                                    'merge_table_ids' => [
                                        "{$selectedTable->table_name} already has a reservation during the selected time.",
                                    ],
                                ]);
                            }
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Update selected table
                |--------------------------------------------------------------------------
                */

                $currentTable->update([
                    'table_name' =>
                        $validated[
                            'table_name'
                        ],

                    'capacity' =>
                        $validated[
                            'capacity'
                        ],

                    'section' =>
                        $validated[
                            'section'
                        ],

                    'status' =>
                        $storedStatus,

                    'reservation_start_at' =>
                        $isReservation
                            ? $reservationStartAt
                            : null,

                    'reservation_end_at' =>
                        $isReservation
                            ? $reservationEndAt
                            : null,

                    'notes' =>
                        $validated[
                            'notes'
                        ] ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Add new tables to merge group
                |--------------------------------------------------------------------------
                */

                if (
                    $newMergeTableIds
                        ->isNotEmpty()
                ) {
                    RestaurantTable::query()
                        ->whereIn(
                            'id',
                            $newMergeTableIds
                        )
                        ->update([
                            'merged_with_id' =>
                                $masterId,

                            'status' =>
                                $storedStatus,

                            'reservation_start_at' =>
                                $isReservation
                                    ? $reservationStartAt
                                    : null,

                            'reservation_end_at' =>
                                $isReservation
                                    ? $reservationEndAt
                                    : null,
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Synchronize complete merge group
                |--------------------------------------------------------------------------
                */

                $hasMergeGroup =
                    $currentGroup->count() > 1 ||
                    $newMergeTableIds
                        ->isNotEmpty();

                if ($hasMergeGroup) {
                    RestaurantTable::query()
                        ->where(
                            function (
                                Builder $query
                            ) use (
                                $masterId
                            ): void {
                                $query
                                    ->where(
                                        'id',
                                        $masterId
                                    )
                                    ->orWhere(
                                        'merged_with_id',
                                        $masterId
                                    );
                            }
                        )
                        ->update([
                            'status' =>
                                $storedStatus,

                            'reservation_start_at' =>
                                $isReservation
                                    ? $reservationStartAt
                                    : null,

                            'reservation_end_at' =>
                                $isReservation
                                    ? $reservationEndAt
                                    : null,
                        ]);
                }

                return RestaurantTable::query()
                    ->with([
                        'mergedWith',
                        'mergedTables',
                    ])
                    ->findOrFail(
                        $currentTable->id
                    );
            }
        );

        return response()->json([
            'success' => true,

            'message' =>
                $isReservation
                    ? 'Restaurant table reservation updated successfully.'
                    : 'Restaurant table updated successfully.',

            'data' =>
                new RestaurantTableResource(
                    $result
                ),
        ]);
    }

    /**
     * Get edit information and merge options.
     */
    public function editOptions(
        RestaurantTable $restaurantTable
    ): JsonResponse {
        $masterId =
            $restaurantTable->merged_with_id
                ? (int)
                    $restaurantTable
                        ->merged_with_id
                : (int)
                    $restaurantTable->id;

        $currentGroup =
            RestaurantTable::query()
                ->where(
                    function (
                        Builder $query
                    ) use (
                        $masterId
                    ): void {
                        $query
                            ->where(
                                'id',
                                $masterId
                            )
                            ->orWhere(
                                'merged_with_id',
                                $masterId
                            );
                    }
                )
                ->orderBy('id')
                ->get();

        $currentGroupIds =
            $currentGroup
                ->pluck('id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                );

        $availableTables =
            RestaurantTable::query()
                ->where(
                    'status',
                    RestaurantTable::STATUS_AVAILABLE
                )
                ->whereNull(
                    'merged_with_id'
                )
                ->whereNotIn(
                    'id',
                    $currentGroupIds
                )
                ->whereDoesntHave(
                    'mergedTables'
                )
                ->orderBy(
                    'table_name'
                )
                ->get();

        return response()->json([
            'success' => true,

            'message' =>
                'Table edit options retrieved successfully.',

            'data' => [
                'master_table_id' =>
                    $masterId,

                'is_existing_merge_group' =>
                    $currentGroup->count() > 1,

                'current_group' =>
                    $currentGroup
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

                                'status' =>
                                    $table->status,

                                'current_status' =>
                                    $table
                                        ->current_status,

                                'section' =>
                                    $table->section,

                                'is_master' =>
                                    (int) $table->id ===
                                    $masterId,
                            ]
                        )
                        ->values(),

                'available_tables' =>
                    $availableTables
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

                                'status' =>
                                    $table->status,

                                'current_status' =>
                                    $table
                                        ->current_status,

                                'section' =>
                                    $table->section,

                                'reservation_start_at' =>
                                    $table
                                        ->reservation_start_at
                                        ?->toISOString(),

                                'reservation_end_at' =>
                                    $table
                                        ->reservation_end_at
                                        ?->toISOString(),
                            ]
                        )
                        ->values(),
            ],
        ]);
    }

    /**
     * Delete restaurant table.
     */
    public function destroy(
        RestaurantTable $restaurantTable
    ): JsonResponse {
        if (
            $restaurantTable
                ->current_status ===
            RestaurantTable::STATUS_OCCUPIED
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'An occupied table cannot be deleted.',
            ], 422);
        }

        if (
            $restaurantTable
                ->has_active_reservation ||
            $restaurantTable
                ->has_upcoming_reservation
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'A table with an active or upcoming reservation cannot be deleted.',
            ], 422);
        }

        $hasMergedTables =
            RestaurantTable::query()
                ->where(
                    'merged_with_id',
                    $restaurantTable->id
                )
                ->exists();

        if (
            $restaurantTable
                ->merged_with_id ||
            $hasMergedTables
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'A merged table cannot be deleted. Split it first.',
            ], 422);
        }

        $tableName =
            $restaurantTable
                ->table_name;

        $restaurantTable->delete();

        return response()->json([
            'success' => true,

            'message' =>
                "{$tableName} deleted successfully.",
        ]);
    }

    /**
     * Get eligible tables for merge.
     */
    public function mergeOptions(
        RestaurantTable $restaurantTable
    ): JsonResponse {
        $masterId =
            $restaurantTable
                ->getMergeMasterId();

        $groupTables =
            RestaurantTable::query()
                ->where(
                    function (
                        Builder $query
                    ) use (
                        $masterId
                    ): void {
                        $query
                            ->where(
                                'id',
                                $masterId
                            )
                            ->orWhere(
                                'merged_with_id',
                                $masterId
                            );
                    }
                )
                ->orderBy('id')
                ->get();

        $groupIds =
            $groupTables
                ->pluck('id')
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->values();

        $isExistingGroup =
            $groupTables->count() > 1;

        if (
            !$isExistingGroup &&
            $restaurantTable
                ->current_status !==
            RestaurantTable::STATUS_AVAILABLE
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'Only an available table can start a new merge group.',

                'data' => [
                    'current_group' => [],
                    'available_tables' => [],
                ],
            ], 422);
        }

        $now = now();

        $availableTables =
            RestaurantTable::query()
                ->where(
                    'status',
                    RestaurantTable::STATUS_AVAILABLE
                )
                ->whereNull(
                    'merged_with_id'
                )
                ->whereNotIn(
                    'id',
                    $groupIds
                )
                ->whereDoesntHave(
                    'mergedTables'
                )
                ->where(
                    function (
                        Builder $builder
                    ) use (
                        $now
                    ): void {
                        $builder
                            ->whereNull(
                                'reservation_start_at'
                            )
                            ->orWhereNull(
                                'reservation_end_at'
                            )
                            ->orWhere(
                                'reservation_start_at',
                                '>',
                                $now
                            )
                            ->orWhere(
                                'reservation_end_at',
                                '<=',
                                $now
                            );
                    }
                )
                ->orderBy(
                    'table_name'
                )
                ->get();

        return response()->json([
            'success' => true,

            'message' =>
                'Merge options retrieved successfully.',

            'data' => [
                'master_table_id' =>
                    $masterId,

                'is_existing_group' =>
                    $isExistingGroup,

                'current_group' =>
                    $groupTables
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

                                'status' =>
                                    $table->status,

                                'current_status' =>
                                    $table
                                        ->current_status,
                            ]
                        )
                        ->values(),

                'available_tables' =>
                    $availableTables
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

                                'status' =>
                                    $table->status,

                                'current_status' =>
                                    $table
                                        ->current_status,
                            ]
                        )
                        ->values(),
            ],
        ]);
    }

    /**
     * Create or extend merge group.
     */
    public function merge(
        Request $request,
        RestaurantTable $restaurantTable
    ): JsonResponse {
        $validated = $request->validate([
            'table_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'table_ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:restaurant_tables,id',
            ],
        ]);

        $requestedTableIds = collect(
            $validated['table_ids']
        )
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->unique()
            ->values();

        if (
            $requestedTableIds
                ->contains(
                    (int)
                        $restaurantTable->id
                )
        ) {
            throw ValidationException::withMessages([
                'table_ids' => [
                    'The current table cannot be selected again.',
                ],
            ]);
        }

        $result = DB::transaction(
            function () use (
                $restaurantTable,
                $requestedTableIds
            ): array {
                $currentTable =
                    RestaurantTable::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $restaurantTable->id
                        );

                $masterId =
                    $currentTable
                        ->getMergeMasterId();

                $currentGroup =
                    RestaurantTable::query()
                        ->where(
                            function (
                                Builder $query
                            ) use (
                                $masterId
                            ): void {
                                $query
                                    ->where(
                                        'id',
                                        $masterId
                                    )
                                    ->orWhere(
                                        'merged_with_id',
                                        $masterId
                                    );
                            }
                        )
                        ->lockForUpdate()
                        ->get();

                $isExistingGroup =
                    $currentGroup->count() > 1;

                if (
                    !$isExistingGroup &&
                    $currentTable
                        ->current_status !==
                    RestaurantTable::STATUS_AVAILABLE
                ) {
                    throw ValidationException::withMessages([
                        'table_ids' => [
                            'Only an available table can start a merge group.',
                        ],
                    ]);
                }

                $selectedTables =
                    RestaurantTable::query()
                        ->whereIn(
                            'id',
                            $requestedTableIds
                        )
                        ->lockForUpdate()
                        ->get();

                if (
                    $selectedTables
                        ->count() !==
                    $requestedTableIds
                        ->count()
                ) {
                    throw ValidationException::withMessages([
                        'table_ids' => [
                            'One or more selected tables no longer exist.',
                        ],
                    ]);
                }

                $currentGroupIds =
                    $currentGroup
                        ->pluck('id')
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        );

                foreach (
                    $selectedTables
                    as $selectedTable
                ) {
                    if (
                        $currentGroupIds
                            ->contains(
                                (int)
                                    $selectedTable->id
                            )
                    ) {
                        throw ValidationException::withMessages([
                            'table_ids' => [
                                "{$selectedTable->table_name} is already part of this group.",
                            ],
                        ]);
                    }

                    if (
                        $selectedTable
                            ->current_status !==
                        RestaurantTable::STATUS_AVAILABLE
                    ) {
                        throw ValidationException::withMessages([
                            'table_ids' => [
                                "{$selectedTable->table_name} is not currently available.",
                            ],
                        ]);
                    }

                    if (
                        $selectedTable
                            ->merged_with_id !==
                        null
                    ) {
                        throw ValidationException::withMessages([
                            'table_ids' => [
                                "{$selectedTable->table_name} already belongs to another merge group.",
                            ],
                        ]);
                    }

                    $controlsAnotherGroup =
                        RestaurantTable::query()
                            ->where(
                                'merged_with_id',
                                $selectedTable->id
                            )
                            ->lockForUpdate()
                            ->exists();

                    if ($controlsAnotherGroup) {
                        throw ValidationException::withMessages([
                            'table_ids' => [
                                "{$selectedTable->table_name} controls another merge group.",
                            ],
                        ]);
                    }
                }

                RestaurantTable::query()
                    ->whereIn(
                        'id',
                        $requestedTableIds
                    )
                    ->update([
                        'merged_with_id' =>
                            $masterId,

                        'status' =>
                            RestaurantTable::STATUS_OCCUPIED,

                        'reservation_start_at' =>
                            null,

                        'reservation_end_at' =>
                            null,
                    ]);

                RestaurantTable::query()
                    ->where(
                        function (
                            Builder $query
                        ) use (
                            $masterId
                        ): void {
                            $query
                                ->where(
                                    'id',
                                    $masterId
                                )
                                ->orWhere(
                                    'merged_with_id',
                                    $masterId
                                );
                        }
                    )
                    ->update([
                        'status' =>
                            RestaurantTable::STATUS_OCCUPIED,

                        'reservation_start_at' =>
                            null,

                        'reservation_end_at' =>
                            null,
                    ]);

                $updatedGroup =
                    RestaurantTable::query()
                        ->where(
                            function (
                                Builder $query
                            ) use (
                                $masterId
                            ): void {
                                $query
                                    ->where(
                                        'id',
                                        $masterId
                                    )
                                    ->orWhere(
                                        'merged_with_id',
                                        $masterId
                                    );
                            }
                        )
                        ->orderBy('id')
                        ->get();

                return [
                    'master_table_id' =>
                        $masterId,

                    'table_ids' =>
                        $updatedGroup
                            ->pluck('id')
                            ->map(
                                fn ($id): int =>
                                    (int) $id
                            )
                            ->values()
                            ->all(),

                    'total_capacity' =>
                        (int) $updatedGroup
                            ->sum('capacity'),

                    'status' =>
                        RestaurantTable::STATUS_OCCUPIED,

                    'tables' =>
                        $updatedGroup
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

                                    'status' =>
                                        $table->status,

                                    'merged_with_id' =>
                                        $table->merged_with_id
                                            ? (int)
                                                $table
                                                    ->merged_with_id
                                            : null,
                                ]
                            )
                            ->values()
                            ->all(),
                ];
            }
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Tables merged and marked as occupied successfully.',

            'data' =>
                $result,
        ]);
    }

    /**
     * Split complete merge group.
     */
    public function split(
        RestaurantTable $restaurantTable
    ): JsonResponse {
        $result = DB::transaction(
            function () use (
                $restaurantTable
            ): array {
                $selectedTable =
                    RestaurantTable::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $restaurantTable->id
                        );

                $masterId =
                    $selectedTable
                        ->getMergeMasterId();

                $mergeGroup =
                    RestaurantTable::query()
                        ->where(
                            function (
                                Builder $query
                            ) use (
                                $masterId
                            ): void {
                                $query
                                    ->where(
                                        'id',
                                        $masterId
                                    )
                                    ->orWhere(
                                        'merged_with_id',
                                        $masterId
                                    );
                            }
                        )
                        ->lockForUpdate()
                        ->orderBy('id')
                        ->get();

                if (
                    $mergeGroup->count() < 2
                ) {
                    throw ValidationException::withMessages([
                        'table' => [
                            'This table is not part of a merged group.',
                        ],
                    ]);
                }

                $splitTableIds =
                    $mergeGroup
                        ->pluck('id')
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->values();

                RestaurantTable::query()
                    ->whereIn(
                        'id',
                        $splitTableIds
                    )
                    ->update([
                        'merged_with_id' =>
                            null,

                        'status' =>
                            RestaurantTable::STATUS_AVAILABLE,

                        'reservation_start_at' =>
                            null,

                        'reservation_end_at' =>
                            null,
                    ]);

                $updatedTables =
                    RestaurantTable::query()
                        ->whereIn(
                            'id',
                            $splitTableIds
                        )
                        ->orderBy('id')
                        ->get();

                return [
                    'previous_master_table_id' =>
                        $masterId,

                    'split_table_ids' =>
                        $splitTableIds->all(),

                    'split_table_count' =>
                        $splitTableIds
                            ->count(),

                    'tables' =>
                        $updatedTables
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

                                    'status' =>
                                        $table->status,

                                    'merged_with_id' =>
                                        null,
                                ]
                            )
                            ->values()
                            ->all(),
                ];
            }
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Merged table group split successfully.',

            'data' =>
                $result,
        ]);
    }

    /**
     * Query tables that are available right now.
     */
    private function availableNowQuery(
        Carbon $now
    ): Builder {
        return RestaurantTable::query()
            ->where(
                'status',
                RestaurantTable::STATUS_AVAILABLE
            )
            ->where(
                function (
                    Builder $builder
                ) use (
                    $now
                ): void {
                    $builder
                        ->whereNull(
                            'reservation_start_at'
                        )
                        ->orWhereNull(
                            'reservation_end_at'
                        )
                        ->orWhere(
                            'reservation_start_at',
                            '>',
                            $now
                        )
                        ->orWhere(
                            'reservation_end_at',
                            '<=',
                            $now
                        );
                }
            );
    }
}