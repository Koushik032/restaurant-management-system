<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;


    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'role_id',
        'name',
        'username',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'last_logout_at',
        'failed_login_attempts',
        'blocked_at',
    ];


    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    */

    protected $hidden = [
        'password',
        'remember_token',
    ];


    /*
    |--------------------------------------------------------------------------
    | Attribute Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'role_id' =>
                'integer',

            'email_verified_at' =>
                'datetime',

            'password' =>
                'hashed',

            'is_active' =>
                'boolean',

            'last_login_at' =>
                'datetime',

            'last_logout_at' =>
                'datetime',

            'failed_login_attempts' =>
                'integer',

            'blocked_at' =>
                'datetime',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Role Relationship
    |--------------------------------------------------------------------------
    */

    public function role(): BelongsTo
    {
        return $this->belongsTo(
            Role::class,
            'role_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Employee Relationship
    |--------------------------------------------------------------------------
    */

    public function employee(): HasOne
    {
        return $this->hasOne(
            Employee::class,
            'user_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Expense Relationships
    |--------------------------------------------------------------------------
    */

    public function createdExpenses(): HasMany
    {
        return $this->hasMany(
            Expense::class,
            'created_by'
        );
    }


    public function updatedExpenses(): HasMany
    {
        return $this->hasMany(
            Expense::class,
            'updated_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Kitchen Order Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * All kitchen orders assigned to this chef.
     */
    public function kitchenOrders(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'chef_id'
        );
    }


    /**
     * Orders accepted by this chef and still pending.
     */
    public function acceptedKitchenOrders(): HasMany
    {
        return $this->kitchenOrders()
            ->where(
                'status',
                Order::STATUS_PENDING
            )
            ->whereNotNull(
                'sent_to_kitchen_at'
            );
    }


    /**
     * Orders currently being prepared by this chef.
     */
    public function preparingKitchenOrders(): HasMany
    {
        return $this->kitchenOrders()
            ->where(
                'status',
                Order::STATUS_PREPARING
            );
    }


    /**
     * Orders marked ready by this chef.
     */
    public function readyKitchenOrders(): HasMany
    {
        return $this->kitchenOrders()
            ->where(
                'status',
                Order::STATUS_READY
            );
    }


    /**
     * Kitchen orders already finished or moved beyond ready.
     */
    public function finishedKitchenOrders(): HasMany
    {
        return $this->kitchenOrders()
            ->whereIn(
                'status',
                [
                    Order::STATUS_READY,
                    Order::STATUS_SERVED,
                    Order::STATUS_COMPLETED,
                ]
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    public function hasRole(
        string $role
    ): bool {
        $role = strtolower(
            trim($role)
        );

        if ($role === '') {
            return false;
        }


        $currentRole = strtolower(
            trim(
                (string) (
                    $this->role?->name
                    ?? ''
                )
            )
        );


        return $currentRole === $role;
    }


    /*
    |--------------------------------------------------------------------------
    | Permission Helpers
    |--------------------------------------------------------------------------
    */

    public function hasPermission(
        string $permission
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Normalize Permission
        |--------------------------------------------------------------------------
        */

        $permission = trim(
            $permission
        );


        if ($permission === '') {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Active User Protection
        |--------------------------------------------------------------------------
        */

        if (! $this->is_active) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Blocked User Protection
        |--------------------------------------------------------------------------
        */

        if ($this->blocked_at !== null) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Load Role
        |--------------------------------------------------------------------------
        */

        $this->loadMissing(
            'role'
        );


        /*
        |--------------------------------------------------------------------------
        | Active Role Protection
        |--------------------------------------------------------------------------
        */

        if (
            ! $this->role
            ||
            ! $this->role->is_active
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Load Role Permissions
        |--------------------------------------------------------------------------
        */

        $this->role->loadMissing(
            'permissions'
        );


        /*
        |--------------------------------------------------------------------------
        | Check Permission
        |--------------------------------------------------------------------------
        */

        return $this
            ->role
            ->permissions
            ->contains(
                'name',
                $permission
            );
    }


    public function hasAnyPermission(
        array $permissions
    ): bool {
        if ($permissions === []) {
            return false;
        }


        foreach ($permissions as $permission) {

            if (! is_string($permission)) {
                continue;
            }


            if (
                $this->hasPermission(
                    $permission
                )
            ) {
                return true;
            }
        }


        return false;
    }
}