<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RestaurantTable extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Section constants
    |--------------------------------------------------------------------------
    */

    public const SECTION_AC = 'ac';

    public const SECTION_NON_AC = 'non_ac';

    public const SECTION_OUTDOOR = 'outdoor';

    /*
    |--------------------------------------------------------------------------
    | Status constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_CLEANING = 'cleaning';

    /*
    |--------------------------------------------------------------------------
    | Mass assignable fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'table_name',
        'capacity',
        'section',
        'status',
        'reservation_start_at',
        'reservation_end_at',
        'merged_with_id',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',

            'merged_with_id' => 'integer',

            'reservation_start_at' =>
                'datetime',

            'reservation_end_at' =>
                'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Parent/master table of this merged table.
     */
    public function mergedWith(): BelongsTo
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'merged_with_id'
        );
    }

    /**
     * Child tables belonging to this master table.
     */
    public function mergedTables(): HasMany
    {
        return $this->hasMany(
            RestaurantTable::class,
            'merged_with_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Effective status accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the table's effective current status.
     *
     * Priority:
     *
     * 1. Occupied
     * 2. Cleaning
     * 3. Active reservation
     * 4. Available
     *
     * Future reservation does not block the table now.
     * Expired reservation also does not block the table.
     */
    protected function currentStatus(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                /*
                |--------------------------------------------------------------------------
                | Occupied gets highest priority
                |--------------------------------------------------------------------------
                */

                if (
                    $this->status ===
                    self::STATUS_OCCUPIED
                ) {
                    return self::STATUS_OCCUPIED;
                }

                /*
                |--------------------------------------------------------------------------
                | Cleaning gets second priority
                |--------------------------------------------------------------------------
                */

                if (
                    $this->status ===
                    self::STATUS_CLEANING
                ) {
                    return self::STATUS_CLEANING;
                }

                /*
                |--------------------------------------------------------------------------
                | Active reservation
                |--------------------------------------------------------------------------
                */

                if (
                    $this
                        ->has_active_reservation
                ) {
                    return self::STATUS_RESERVED;
                }

                /*
                |--------------------------------------------------------------------------
                | Future or expired reservation
                |--------------------------------------------------------------------------
                */

                return self::STATUS_AVAILABLE;
            }
        );
    }

    /**
     * Determine whether reservation is active now.
     */
    protected function hasActiveReservation(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (
                    !$this->reservation_start_at ||
                    !$this->reservation_end_at
                ) {
                    return false;
                }

                $now = now();

                return (
                    $now->greaterThanOrEqualTo(
                        $this->reservation_start_at
                    ) &&
                    $now->lessThan(
                        $this->reservation_end_at
                    )
                );
            }
        );
    }

    /**
     * Determine whether reservation starts in the future.
     */
    protected function hasUpcomingReservation(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (
                    !$this->reservation_start_at ||
                    !$this->reservation_end_at
                ) {
                    return false;
                }

                return (
                    now()->lessThan(
                        $this->reservation_start_at
                    ) &&
                    $this->reservation_end_at
                        ->greaterThan(
                            now()
                        )
                );
            }
        );
    }

    /**
     * Determine whether reservation has expired.
     */
    protected function hasExpiredReservation(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (
                    !$this->reservation_start_at ||
                    !$this->reservation_end_at
                ) {
                    return false;
                }

                return now()
                    ->greaterThanOrEqualTo(
                        $this->reservation_end_at
                    );
            }
        );
    }

    /**
     * Determine whether the table has any reservation schedule.
     */
    protected function hasReservationSchedule(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->reservation_start_at !==
                    null &&
                $this->reservation_end_at !==
                    null
        );
    }

    /**
     * Get reservation state.
     */
    protected function reservationType(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (
                    !$this
                        ->has_reservation_schedule
                ) {
                    return null;
                }

                if (
                    $this
                        ->has_active_reservation
                ) {
                    return 'active';
                }

                if (
                    $this
                        ->has_upcoming_reservation
                ) {
                    return 'upcoming';
                }

                return 'expired';
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Merge helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether this table is a child
     * of another merged table.
     */
    public function isMergeChild(): bool
    {
        return $this->merged_with_id !==
            null;
    }

    /**
     * Determine whether this table controls
     * one or more child tables.
     */
    public function isMergeMaster(): bool
    {
        return $this
            ->mergedTables()
            ->exists();
    }

    /**
     * Determine whether table belongs to any
     * merged table group.
     */
    public function isMerged(): bool
    {
        return (
            $this->isMergeChild() ||
            $this->isMergeMaster()
        );
    }

    /**
     * Get merge master table ID.
     */
    public function getMergeMasterId(): int
    {
        return $this->merged_with_id !==
            null
                ? (int)
                    $this->merged_with_id
                : (int) $this->id;
    }

    /**
     * Get all tables belonging to this merge group.
     */
    public function getMergeGroup(): Collection
    {
        $masterId =
            $this->getMergeMasterId();

        return RestaurantTable::query()
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
    }

    /*
    |--------------------------------------------------------------------------
    | Reservation helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether a requested reservation
     * overlaps this table's existing reservation.
     */
    public function hasReservationConflict(
        mixed $requestedStartAt,
        mixed $requestedEndAt
    ): bool {
        if (
            !$this->reservation_start_at ||
            !$this->reservation_end_at
        ) {
            return false;
        }

        return (
            $this->reservation_start_at
                ->lessThan(
                    $requestedEndAt
                ) &&
            $this->reservation_end_at
                ->greaterThan(
                    $requestedStartAt
                )
        );
    }

    /**
     * Clear reservation schedule from table.
     */
    public function clearReservation(): bool
    {
        return $this->update([
            'reservation_start_at' =>
                null,

            'reservation_end_at' =>
                null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Query scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Search by table name, notes or ID.
     */
    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim(
            (string) $search
        );

        if ($search === '') {
            return $query;
        }

        return $query->where(
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

                if (
                    is_numeric($search)
                ) {
                    $builder->orWhere(
                        'id',
                        (int) $search
                    );
                }
            }
        );
    }

    /**
     * Filter by section.
     */
    public function scopeSection(
        Builder $query,
        ?string $section
    ): Builder {
        if (!$section) {
            return $query;
        }

        return $query->where(
            'section',
            $section
        );
    }

    /**
     * Filter by raw database status.
     */
    public function scopeStatus(
        Builder $query,
        ?string $status
    ): Builder {
        if (!$status) {
            return $query;
        }

        return $query->where(
            'status',
            $status
        );
    }

    /**
     * Filter by minimum capacity.
     */
    public function scopeCapacity(
        Builder $query,
        ?int $capacity
    ): Builder {
        if (
            !$capacity ||
            $capacity < 1
        ) {
            return $query;
        }

        return $query->where(
            'capacity',
            '>=',
            $capacity
        );
    }

    /**
     * Filter tables currently occupied.
     */
    public function scopeOccupied(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_OCCUPIED
        );
    }

    /**
     * Filter tables currently cleaning.
     */
    public function scopeCleaning(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_CLEANING
        );
    }

    /**
     * Filter active reservations.
     */
    public function scopeActiveReservation(
        Builder $query
    ): Builder {
        $now = now();

        return $query
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
            );
    }

    /**
     * Filter upcoming reservations.
     */
    public function scopeUpcomingReservation(
        Builder $query
    ): Builder {
        $now = now();

        return $query
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
            ->where(
                'reservation_end_at',
                '>',
                $now
            );
    }

    /**
     * Filter active and upcoming reservations.
     */
    public function scopeValidReservation(
        Builder $query
    ): Builder {
        return $query
            ->whereNotNull(
                'reservation_start_at'
            )
            ->whereNotNull(
                'reservation_end_at'
            )
            ->where(
                'reservation_end_at',
                '>',
                now()
            );
    }

    /**
     * Filter tables available right now.
     */
    public function scopeAvailableNow(
        Builder $query
    ): Builder {
        $now = now();

        return $query
            ->where(
                'status',
                self::STATUS_AVAILABLE
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

    public function primaryOrders(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'restaurant_table_id'
        );
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(
            Order::class,
            'order_tables',
            'restaurant_table_id',
            'order_id'
        )
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function activeOrders(): BelongsToMany
    {
        return $this->orders()
            ->whereIn(
                'orders.status',
                Order::activeStatuses()
            );
    }
}