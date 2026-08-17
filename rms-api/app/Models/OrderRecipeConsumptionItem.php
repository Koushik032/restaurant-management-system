<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;


class OrderRecipeConsumptionItem extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'order_recipe_consumption_id',
        'raw_material_id',
        'material_name',
        'unit',
        'quantity',
        'unit_cost',
        'restaurant_quantity_before',
        'restaurant_quantity_after',
        'source_breakdown',
        'notes',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'order_recipe_consumption_id' =>
                'integer',

            'raw_material_id' =>
                'integer',

            'quantity' =>
                'decimal:4',

            'unit_cost' =>
                'decimal:4',

            'restaurant_quantity_before' =>
                'decimal:4',

            'restaurant_quantity_after' =>
                'decimal:4',

            'source_breakdown' =>
                'array',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Model Integrity / Immutable Ledger
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(
            function (
                OrderRecipeConsumptionItem $item
            ): void {

                $item->order_recipe_consumption_id =
                    (int) $item
                        ->order_recipe_consumption_id;


                $item->raw_material_id =
                    (int) $item
                        ->raw_material_id;


                $item->material_name =
                    trim(
                        (string) $item
                            ->material_name
                    );


                $item->unit =
                    strtolower(
                        trim(
                            (string) $item
                                ->unit
                        )
                    );


                $item->quantity =
                    round(
                        (float) $item
                            ->quantity,
                        4
                    );


                $item->unit_cost =
                    round(
                        (float) (
                            $item->unit_cost
                            ??
                            0
                        ),
                        4
                    );


                $item->restaurant_quantity_before =
                    round(
                        (float) $item
                            ->restaurant_quantity_before,
                        4
                    );


                $item->restaurant_quantity_after =
                    round(
                        (float) $item
                            ->restaurant_quantity_after,
                        4
                    );


                $item->notes =
                    static::normalizeNullableText(
                        $item->notes
                    );


                /*
                |--------------------------------------------------------------------------
                | Parent Consumption
                |--------------------------------------------------------------------------
                */

                if (
                    $item->order_recipe_consumption_id
                    <=
                    0
                ) {
                    throw new InvalidArgumentException(
                        'A valid recipe consumption record is required.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Raw Material
                |--------------------------------------------------------------------------
                */

                if (
                    $item->raw_material_id
                    <=
                    0
                ) {
                    throw new InvalidArgumentException(
                        'A valid raw material is required for recipe consumption.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Material Snapshot
                |--------------------------------------------------------------------------
                */

                if (
                    $item->material_name
                    ===
                    ''
                ) {
                    throw new InvalidArgumentException(
                        'Raw material name snapshot is required.'
                    );
                }


                if (
                    $item->unit
                    ===
                    ''
                ) {
                    throw new InvalidArgumentException(
                        'Raw material unit snapshot is required.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Quantity
                |--------------------------------------------------------------------------
                */

                if (
                    (float) $item->quantity
                    <=
                    0
                ) {
                    throw new InvalidArgumentException(
                        'Recipe consumption quantity must be greater than zero.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Unit Cost
                |--------------------------------------------------------------------------
                */

                if (
                    (float) $item->unit_cost
                    <
                    0
                ) {
                    throw new InvalidArgumentException(
                        'Recipe consumption unit cost cannot be negative.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Restaurant Stock Snapshots
                |--------------------------------------------------------------------------
                */

                if (
                    (float) $item
                        ->restaurant_quantity_before
                    <
                    0
                ) {
                    throw new InvalidArgumentException(
                        'Restaurant stock quantity before consumption cannot be negative.'
                    );
                }


                if (
                    (float) $item
                        ->restaurant_quantity_after
                    <
                    0
                ) {
                    throw new InvalidArgumentException(
                        'Restaurant stock quantity after consumption cannot be negative.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Snapshot Arithmetic
                |--------------------------------------------------------------------------
                |
                | before - consumed = after
                |
                */

                $expectedAfter =
                    round(
                        (float) $item
                            ->restaurant_quantity_before
                        -
                        (float) $item
                            ->quantity,
                        4
                    );


                if (
                    abs(
                        $expectedAfter
                        -
                        (float) $item
                            ->restaurant_quantity_after
                    )
                    >
                    0.0001
                ) {
                    throw new InvalidArgumentException(
                        'Restaurant stock snapshots do not match the consumed quantity.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Source Breakdown
                |--------------------------------------------------------------------------
                */

                if (
                    $item->source_breakdown !== null
                    &&
                    !is_array(
                        $item->source_breakdown
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Recipe consumption source breakdown must be a valid array.'
                    );
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Immutable
        |--------------------------------------------------------------------------
        */

        static::updating(
            static function (): never {
                throw new LogicException(
                    'Order recipe consumption item history is immutable and cannot be updated.'
                );
            }
        );


        static::deleting(
            static function (): never {
                throw new LogicException(
                    'Order recipe consumption item history is immutable and cannot be deleted.'
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function consumption(): BelongsTo
    {
        return $this->belongsTo(
            OrderRecipeConsumption::class,
            'order_recipe_consumption_id'
        );
    }


    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(
            RawMaterial::class,
            'raw_material_id'
        )->withTrashed();
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private static function normalizeNullableText(
        mixed $value
    ): ?string {

        if (
            $value === null
        ) {
            return null;
        }


        $value =
            trim(
                (string) $value
            );


        return $value !== ''
            ? $value
            : null;
    }
}