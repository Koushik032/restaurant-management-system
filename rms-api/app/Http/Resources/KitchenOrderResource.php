<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KitchenOrderResource extends JsonResource
{
    /**
     * Transform the kitchen order into an API response.
     */
    public function toArray(
        Request $request
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Authenticated User
        |--------------------------------------------------------------------------
        */

        $authenticatedUserId =
            $request->user()
                ?->id;

        $isAssignedToCurrentUser =
            $authenticatedUserId !== null
            && $this->chef_id !== null
            && (int) $this->chef_id
                === (int) $authenticatedUserId;

        /*
        |--------------------------------------------------------------------------
        | Recipe Consumption State
        |--------------------------------------------------------------------------
        |
        | Resource must not trigger hidden/lazy queries.
        |
        */

        $recipeConsumptionLoaded =
            $this->relationLoaded(
                'recipeConsumption'
            );

        $recipeConsumption =
            $recipeConsumptionLoaded
                ? $this->recipeConsumption
                : null;

        /*
        |--------------------------------------------------------------------------
        | Main Response
        |--------------------------------------------------------------------------
        */

        return [
            /*
            |--------------------------------------------------------------------------
            | Basic Order Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,

            'order_number' =>
                $this->order_number,

            'status' =>
                $this->status,

            'status_label' =>
                $this->kitchenStatusLabel(),

            /*
            |--------------------------------------------------------------------------
            | Assignment State
            |--------------------------------------------------------------------------
            */

            'chef_id' =>
                $this->chef_id
                    ? (int) $this->chef_id
                    : null,

            'is_assigned' =>
                $this->chef_id !== null,

            'is_assigned_to_current_user' =>
                $isAssignedToCurrentUser,

            /*
            |--------------------------------------------------------------------------
            | Assigned Chef
            |--------------------------------------------------------------------------
            */

            'chef' =>
                $this->whenLoaded(
                    'chef',
                    function (): ?array {
                        if (! $this->chef) {
                            return null;
                        }

                        return [
                            'id' =>
                                (int) $this->chef->id,

                            'name' =>
                                $this->chef->name,

                            'username' =>
                                $this->chef->username,

                            'display_name' =>
                                $this->chef->username
                                ?: $this->chef->name
                                ?: 'Unknown Chef',
                        ];
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Customer Snapshot
            |--------------------------------------------------------------------------
            */

            'customer' => [
                'name' =>
                    $this->customer_name
                    ?: 'Walk-in Customer',

                'phone' =>
                    $this->customer_phone,
            ],

            /*
            |--------------------------------------------------------------------------
            | Primary Table
            |--------------------------------------------------------------------------
            */

            'primary_table' =>
                $this->whenLoaded(
                    'primaryTable',
                    function (): ?array {
                        if (! $this->primaryTable) {
                            return null;
                        }

                        return [
                            'id' =>
                                (int)
                                    $this
                                        ->primaryTable
                                        ->id,

                            'table_name' =>
                                $this
                                    ->primaryTable
                                    ->table_name,

                            'section' =>
                                $this
                                    ->primaryTable
                                    ->section,

                            'capacity' =>
                                (int) (
                                    $this
                                        ->primaryTable
                                        ->capacity
                                    ?? 0
                                ),

                            'status' =>
                                $this
                                    ->primaryTable
                                    ->status,
                        ];
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Merged Tables
            |--------------------------------------------------------------------------
            */

            'merged_tables' =>
                $this->whenLoaded(
                    'tables',
                    function () {
                        return $this->tables
                            ->filter(
                                static fn (
                                    $table
                                ): bool =>
                                    ! (bool) (
                                        $table
                                            ->pivot
                                            ?->is_primary
                                        ?? false
                                    )
                            )
                            ->map(
                                static function (
                                    $table
                                ): array {
                                    return [
                                        'id' =>
                                            (int)
                                                $table->id,

                                        'table_name' =>
                                            $table
                                                ->table_name,

                                        'section' =>
                                            $table
                                                ->section,

                                        'capacity' =>
                                            (int) (
                                                $table
                                                    ->capacity
                                                ?? 0
                                            ),
                                    ];
                                }
                            )
                            ->values();
                    }
                ),

            'merged_table_names' =>
                $this->whenLoaded(
                    'tables',
                    function (): string {
                        return $this->tables
                            ->filter(
                                static fn (
                                    $table
                                ): bool =>
                                    ! (bool) (
                                        $table
                                            ->pivot
                                            ?->is_primary
                                        ?? false
                                    )
                            )
                            ->pluck(
                                'table_name'
                            )
                            ->filter()
                            ->implode(', ');
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Order and Kitchen Notes
            |--------------------------------------------------------------------------
            */

            'order_note' =>
                $this->order_note,

            'kitchen_note' =>
                $this->kitchen_note,

            /*
            |--------------------------------------------------------------------------
            | Ordered Menu Items
            |--------------------------------------------------------------------------
            */

            'items' =>
                $this->whenLoaded(
                    'items',
                    function () {
                        return $this->items
                            ->map(
                                function (
                                    $item
                                ): array {
                                    $menuItem =
                                        $item
                                            ->relationLoaded(
                                                'menuItem'
                                            )
                                            ? $item
                                                ->menuItem
                                            : null;

                                    return [
                                        /*
                                        |--------------------------------------------------------------------------
                                        | Basic Item Information
                                        |--------------------------------------------------------------------------
                                        */

                                        'id' =>
                                            (int)
                                                $item->id,

                                        'menu_item_id' =>
                                            $item
                                                ->menu_item_id
                                                ? (int)
                                                    $item
                                                        ->menu_item_id
                                                : null,

                                        'menu_item_variant_id' =>
                                            $item
                                                ->menu_item_variant_id
                                                ? (int)
                                                    $item
                                                        ->menu_item_variant_id
                                                : null,

                                        'item_name' =>
                                            $item
                                                ->item_name,

                                        'variant_name' =>
                                            $item
                                                ->variant_name,

                                        'quantity' =>
                                            (int)
                                                $item
                                                    ->quantity,

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Item Kitchen Status
                                        |--------------------------------------------------------------------------
                                        */

                                        'status' =>
                                            $item
                                                ->status,

                                        'status_label' =>
                                            $this
                                                ->formatLabel(
                                                    $item
                                                        ->status
                                                ),

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Item Kitchen Note
                                        |--------------------------------------------------------------------------
                                        */

                                        'kitchen_note' =>
                                            $item
                                                ->kitchen_note,

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Menu Item Ingredients
                                        |--------------------------------------------------------------------------
                                        |
                                        | Ingredients menu_items.ingredients plain-text
                                        | column থেকে নেওয়া হচ্ছে।
                                        |
                                        */

                                        'ingredients' =>
                                            $menuItem
                                                ?->ingredients,

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Preparation Information
                                        |--------------------------------------------------------------------------
                                        */

                                        'preparation_time' =>
                                            $menuItem
                                                ?->preparation_time
                                                !== null
                                                ? (int)
                                                    $menuItem
                                                        ->preparation_time
                                                : null,

                                        'image_url' =>
                                            $menuItem
                                                ?->image_url,

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Item Add-ons
                                        |--------------------------------------------------------------------------
                                        */

                                        'addons' =>
                                            $item
                                                ->relationLoaded(
                                                    'addons'
                                                )
                                                ? $item
                                                    ->addons
                                                    ->map(
                                                        static function (
                                                            $addon
                                                        ): array {
                                                            return [
                                                                'id' =>
                                                                    (int)
                                                                        $addon
                                                                            ->id,

                                                                'menu_addon_id' =>
                                                                    $addon
                                                                        ->menu_addon_id
                                                                        ? (int)
                                                                            $addon
                                                                                ->menu_addon_id
                                                                        : null,

                                                                'addon_name' =>
                                                                    $addon
                                                                        ->addon_name,

                                                                'quantity' =>
                                                                    (int)
                                                                        $addon
                                                                            ->quantity,
                                                            ];
                                                        }
                                                    )
                                                    ->values()
                                                : [],
                                    ];
                                }
                            )
                            ->values();
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Item Summary
            |--------------------------------------------------------------------------
            */

            'total_item_lines' =>
                $this->whenLoaded(
                    'items',
                    fn (): int =>
                        $this->items->count()
                ),

            'total_item_quantity' =>
                $this->whenLoaded(
                    'items',
                    fn (): int =>
                        (int) $this
                            ->items
                            ->sum('quantity')
                ),

            /*
            |--------------------------------------------------------------------------
            | Kitchen Action State
            |--------------------------------------------------------------------------
            |
            | Admin এবং manager order দেখতে পারবে, কিন্তু assigned chef ছাড়া
            | preparing এবং ready action button active হবে না।
            |
            */

            'can_accept' =>
                $this->canBeAccepted(),

            'can_start_preparing' =>
                $isAssignedToCurrentUser
                && $this
                    ->canStartPreparing(),

            'can_mark_ready' =>
                $isAssignedToCurrentUser
                && $this
                    ->canBeMarkedReady(),

            'is_kitchen_completed' =>
                $this->isKitchenCompleted(),

            /*
            |--------------------------------------------------------------------------
            | Recipe Consumption
            |--------------------------------------------------------------------------
            |
            | null recipe_consumed means the relation was not eager loaded.
            | This avoids reporting a false negative and avoids hidden queries.
            |
            */

            'recipe_consumption_loaded' =>
                $recipeConsumptionLoaded,

            'recipe_consumed' =>
                $recipeConsumptionLoaded
                    ? $recipeConsumption !== null
                    : null,

            'recipe_consumption' =>
                $this->when(
                    $recipeConsumptionLoaded,
                    function () use (
                        $recipeConsumption
                    ): ?array {
                        if (! $recipeConsumption) {
                            return null;
                        }

                        return [
                            'id' =>
                                (int) $recipeConsumption->id,

                            'trigger' =>
                                $recipeConsumption->trigger,

                            'order_status_snapshot' =>
                                $recipeConsumption
                                    ->order_status_snapshot,

                            'consumed_at' =>
                                $recipeConsumption
                                    ->consumed_at
                                    ?->toISOString(),

                            'created_by' =>
                                $recipeConsumption->created_by !== null
                                    ? (int) $recipeConsumption
                                        ->created_by
                                    : null,
                        ];
                    },
                    null
                ),

            /*
            |--------------------------------------------------------------------------
            | Kitchen Timestamps
            |--------------------------------------------------------------------------
            */

            'sent_to_kitchen_at' =>
                $this
                    ->sent_to_kitchen_at
                    ?->toISOString(),

            'preparing_at' =>
                $this
                    ->preparing_at
                    ?->toISOString(),

            'ready_at' =>
                $this
                    ->ready_at
                    ?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Order Creation Information
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'date' =>
                $this->created_at
                    ?->format('d M Y'),

            'time' =>
                $this->created_at
                    ?->format('h:i A'),

            'day' =>
                $this->created_at
                    ?->format('l'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Kitchen Status Label
    |--------------------------------------------------------------------------
    */

    private function kitchenStatusLabel(): string
    {
        return match ($this->status) {
            Order::STATUS_PENDING =>
                $this->chef_id
                    ? 'Accepted'
                    : 'Pending',

            Order::STATUS_PREPARING =>
                'Preparing',

            Order::STATUS_READY =>
                'Ready',

            Order::STATUS_SERVED =>
                'Served',

            Order::STATUS_COMPLETED =>
                'Completed',

            Order::STATUS_CANCELED =>
                'Canceled',

            default =>
                $this->formatLabel(
                    $this->status
                ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Readable Label Formatter
    |--------------------------------------------------------------------------
    */

    private function formatLabel(
        mixed $value
    ): string {
        if (
            $value === null
            || $value === ''
        ) {
            return '';
        }

        return ucwords(
            str_replace(
                '_',
                ' ',
                (string) $value
            )
        );
    }
}