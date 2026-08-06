<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'last_logout_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
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
public function employee(): HasOne
{
    return $this->hasOne(
        Employee::class
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

    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->blocked_at !== null) {
            return false;
        }

        if (! $this->role || ! $this->role->is_active) {
            return false;
        }

        $this->role->loadMissing('permissions');

        return $this->role->permissions
            ->contains('name', $permission);
    }
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
}