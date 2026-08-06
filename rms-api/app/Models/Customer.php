<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Customer Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'phone',
        'email',
        'last_visit_at',
        'total_orders',
        'total_spent',
        'is_active',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'last_visit_at' =>
                'datetime',

            'total_orders' =>
                'integer',

            'total_spent' =>
                'decimal:2',

            'is_active' =>
                'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Default Attribute Values
    |--------------------------------------------------------------------------
    */

    protected $attributes = [
        'total_orders' => 0,
        'total_spent' => 0,
        'is_active' => true,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function orders(): HasMany
    {
        return $this->hasMany(
            Order::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Completed Orders
    |--------------------------------------------------------------------------
    */

    public function completedOrders(): HasMany
    {
        return $this->hasMany(
            Order::class
        )->where(
            'status',
            Order::STATUS_COMPLETED
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Active Scope
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Inactive Scope
    |--------------------------------------------------------------------------
    */

    public function scopeInactive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            false
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Filter Scope
    |--------------------------------------------------------------------------
    |
    | Supported values:
    |
    | - active
    | - inactive
    | - all / empty
    |
    */

    public function scopeStatus(
        Builder $query,
        ?string $status
    ): Builder {
        $resolvedStatus = strtolower(
            trim(
                (string) $status
            )
        );

        if (
            $resolvedStatus ===
            self::STATUS_ACTIVE
        ) {
            return $query->active();
        }

        if (
            $resolvedStatus ===
            self::STATUS_INACTIVE
        ) {
            return $query->inactive();
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Search Scope
    |--------------------------------------------------------------------------
    |
    | Searchable fields:
    |
    | - name
    | - phone
    | - email
    |
    */

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $resolvedSearch = trim(
            (string) $search
        );

        if ($resolvedSearch === '') {
            return $query;
        }

        return $query->where(
            function (
                Builder $builder
            ) use (
                $resolvedSearch
            ): void {
                $builder
                    ->where(
                        'name',
                        'like',
                        "%{$resolvedSearch}%"
                    )
                    ->orWhere(
                        'phone',
                        'like',
                        "%{$resolvedSearch}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$resolvedSearch}%"
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sorting Scope
    |--------------------------------------------------------------------------
    |
    | Supported sort values:
    |
    | - latest
    | - oldest
    | - name_asc
    | - name_desc
    | - visits_high
    | - visits_low
    | - spend_high
    | - spend_low
    | - last_visit_latest
    | - last_visit_oldest
    |
    */

    public function scopeSortBy(
        Builder $query,
        ?string $sort
    ): Builder {
        return match (
            strtolower(
                trim(
                    (string) $sort
                )
            )
        ) {
            'oldest' =>
                $query->oldest(
                    'created_at'
                ),

            'name_asc' =>
                $query
                    ->orderBy(
                        'name'
                    )
                    ->orderBy(
                        'id'
                    ),

            'name_desc' =>
                $query
                    ->orderByDesc(
                        'name'
                    )
                    ->orderByDesc(
                        'id'
                    ),

            'visits_high' =>
                $query
                    ->orderByDesc(
                        'total_orders'
                    )
                    ->orderByDesc(
                        'id'
                    ),

            'visits_low' =>
                $query
                    ->orderBy(
                        'total_orders'
                    )
                    ->orderBy(
                        'id'
                    ),

            'spend_high' =>
                $query
                    ->orderByDesc(
                        'total_spent'
                    )
                    ->orderByDesc(
                        'id'
                    ),

            'spend_low' =>
                $query
                    ->orderBy(
                        'total_spent'
                    )
                    ->orderBy(
                        'id'
                    ),

            'last_visit_latest' =>
                $query
                    ->orderByRaw(
                        'last_visit_at IS NULL'
                    )
                    ->orderByDesc(
                        'last_visit_at'
                    )
                    ->orderByDesc(
                        'id'
                    ),

            'last_visit_oldest' =>
                $query
                    ->orderByRaw(
                        'last_visit_at IS NULL'
                    )
                    ->orderBy(
                        'last_visit_at'
                    )
                    ->orderBy(
                        'id'
                    ),

            default =>
                $query->latest(
                    'created_at'
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Status Label
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active
            ? 'Active'
            : 'Inactive';
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Display Name
    |--------------------------------------------------------------------------
    */

    public function getDisplayNameAttribute(): string
    {
        $name = trim(
            (string) $this->name
        );

        return $name !== ''
            ? $name
            : 'Unnamed Customer';
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Initial
    |--------------------------------------------------------------------------
    */

    public function getInitialAttribute(): string
    {
        $displayName = trim(
            $this->display_name
        );

        if ($displayName === '') {
            return 'C';
        }

        return strtoupper(
            mb_substr(
                $displayName,
                0,
                1
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Total Spent Value
    |--------------------------------------------------------------------------
    */

    public function totalSpentValue(): float
    {
        return is_numeric(
            $this->total_spent
        )
            ? (float) $this->total_spent
            : 0.0;
    }

    /*
    |--------------------------------------------------------------------------
    | Visit Count
    |--------------------------------------------------------------------------
    */

    public function visitCount(): int
    {
        return max(
            0,
            (int) $this->total_orders
        );
    }
}