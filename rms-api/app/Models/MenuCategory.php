<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'category_name',
        'description',
        'is_available',
        'display_order',
    ];

    protected function casts(): array
{
    return [
        'is_available' => 'boolean',
        'display_order' => 'integer',
    ];
}

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function menuItems(): HasMany
    {
        return $this->hasMany(
            MenuItem::class,
            'menu_category_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeUnavailable(Builder $query): Builder
    {
        return $query->where('is_available', false);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('display_order')
            ->orderBy('category_name');
    }
}