<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class MenuItemVariant extends Model
{
    use HasFactory;
    use SoftDeletes;


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'menu_item_id',

        'variant_name',

        'price',

        'is_available',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'menu_item_id' =>
            'integer',

        'price' =>
            'decimal:2',

        'is_available' =>
            'boolean',

        'deleted_at' =>
            'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(
            MenuItem::class,
            'menu_item_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Recipe Mappings
    |--------------------------------------------------------------------------
    |
    | One variant can have multiple recipe mapping rows.
    |
    */

    public function recipeMappings(): HasMany
    {
        return $this->hasMany(
            RecipeMapping::class,
            'variant_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAvailable(
        Builder $query
    ): Builder {
        return $query->where(
            'is_available',
            true
        );
    }


    public function scopeUnavailable(
        Builder $query
    ): Builder {
        return $query->where(
            'is_available',
            false
        );
    }


    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {

        $search = trim(
            (string) $search
        );


        if (
            $search === ''
        ) {
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
                        'variant_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhereHas(
                        'menuItem',
                        function (
                            Builder $itemQuery
                        ) use (
                            $search
                        ): void {

                            $itemQuery->where(
                                'menu_name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            }
        );
    }
}