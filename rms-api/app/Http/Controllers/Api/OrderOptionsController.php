<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderOptionsController extends Controller
{
    /**
     * Return all required options for
     * the Create Order page.
     */
    public function create(
        Request $request
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Available tables
        |--------------------------------------------------------------------------
        |
        | Rules:
        | - Raw status must be available
        | - Active reservation must not exist
        | - Table cannot already be part of a merge group
        | - Table cannot control another merge group
        | - Table cannot have an active order
        */

        $availableTables =
            RestaurantTable::query()
                ->availableNow()
                ->whereNull(
                    'merged_with_id'
                )
                ->whereDoesntHave(
                    'mergedTables'
                )
                ->whereDoesntHave(
                    'activeOrders'
                )
                ->orderBy(
                    'table_name'
                )
                ->get([
                    'id',
                    'table_name',
                    'capacity',
                    'section',
                    'status',
                    'reservation_start_at',
                    'reservation_end_at',
                ])
                ->map(
                    static function (
                        RestaurantTable $table
                    ): array {
                        return [
                            'id' =>
                                (int) $table->id,

                            'table_name' =>
                                $table->table_name,

                            'capacity' =>
                                (int) $table->capacity,

                            'section' =>
                                $table->section,

                            'section_label' =>
                                match (
                                    $table->section
                                ) {
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
                                                $table->section
                                            )
                                        ),
                                },

                            'status' =>
                                $table->current_status,

                            'display_name' =>
                                sprintf(
                                    '%s — %s — Capacity %d',
                                    $table->table_name,
                                    match (
                                        $table->section
                                    ) {
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
                                                    $table->section
                                                )
                                            ),
                                    },
                                    (int) $table->capacity
                                ),
                        ];
                    }
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Available menu items
        |--------------------------------------------------------------------------
        |
        | Each item contains:
        | - Base price
        | - Available variants
        | - Connected available add-ons
        */

        $menuItems =
            MenuItem::query()
                ->available()
                ->with([
                    'category:id,category_name',

                    'variants' =>
                        static function (
                            $query
                        ): void {
                            $query
                                ->available()
                                ->orderBy(
                                    'price'
                                )
                                ->orderBy(
                                    'variant_name'
                                );
                        },

                    'addOns' =>
                        static function (
                            $query
                        ): void {
                            $query
                                ->available()
                                ->orderBy(
                                    'add_on_name'
                                );
                        },
                ])
                ->orderBy(
                    'menu_name'
                )
                ->get()
                ->map(
                    static function (
                        MenuItem $menuItem
                    ): array {
                        return [
                            'id' =>
                                (int) $menuItem->id,

                            'menu_name' =>
                                $menuItem->menu_name,

                            'category_id' =>
                                $menuItem
                                    ->menu_category_id
                                    ? (int)
                                        $menuItem
                                            ->menu_category_id
                                    : null,

                            'category_name' =>
                                $menuItem
                                    ->category
                                    ?->category_name,

                            'item_type' =>
                                $menuItem->item_type,

                            'item_type_label' =>
                                $menuItem
                                    ->item_type_label,

                            'price' =>
                                (float)
                                    $menuItem->price,

                            'price_formatted' =>
                                '৳ '
                                .number_format(
                                    (float)
                                        $menuItem->price,
                                    2
                                ),

                            'display_name' =>
                                sprintf(
                                    '%s — ৳ %s',
                                    $menuItem
                                        ->menu_name,
                                    number_format(
                                        (float)
                                            $menuItem
                                                ->price,
                                        2
                                    )
                                ),

                            'image_url' =>
                                $menuItem
                                    ->image_url,

                            'has_variants' =>
                                $menuItem
                                    ->variants
                                    ->isNotEmpty(),

                            'has_addons' =>
                                $menuItem
                                    ->addOns
                                    ->isNotEmpty(),

                            'variants' =>
                                $menuItem
                                    ->variants
                                    ->map(
                                        static function (
                                            $variant
                                        ): array {
                                            return [
                                                'id' =>
                                                    (int)
                                                        $variant
                                                            ->id,

                                                'menu_item_id' =>
                                                    (int)
                                                        $variant
                                                            ->menu_item_id,

                                                'variant_name' =>
                                                    $variant
                                                        ->variant_name,

                                                'price' =>
                                                    (float)
                                                        $variant
                                                            ->price,

                                                'price_formatted' =>
                                                    '৳ '
                                                    .number_format(
                                                        (float)
                                                            $variant
                                                                ->price,
                                                        2
                                                    ),

                                                'display_name' =>
                                                    sprintf(
                                                        '%s — ৳ %s',
                                                        $variant
                                                            ->variant_name,
                                                        number_format(
                                                            (float)
                                                                $variant
                                                                    ->price,
                                                            2
                                                        )
                                                    ),
                                            ];
                                        }
                                    )
                                    ->values(),

                            'addons' =>
                                $menuItem
                                    ->addOns
                                    ->map(
                                        static function (
                                            $addon
                                        ): array {
                                            return [
                                                'id' =>
                                                    (int)
                                                        $addon
                                                            ->id,

                                                'add_on_name' =>
                                                    $addon
                                                        ->add_on_name,

                                                'price' =>
                                                    (float)
                                                        $addon
                                                            ->price,

                                                'price_formatted' =>
                                                    '৳ '
                                                    .number_format(
                                                        (float)
                                                            $addon
                                                                ->price,
                                                        2
                                                    ),

                                                'display_name' =>
                                                    sprintf(
                                                        '%s — ৳ %s',
                                                        $addon
                                                            ->add_on_name,
                                                        number_format(
                                                            (float)
                                                                $addon
                                                                    ->price,
                                                            2
                                                        )
                                                    ),
                                            ];
                                        }
                                    )
                                    ->values(),
                        ];
                    }
                )
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Authenticated waiter
        |--------------------------------------------------------------------------
        */

        $user = $request->user();

        $waiter = $user
            ? [
                'id' =>
                    (int) $user->id,

                /*
                 * Project অনুযায়ী name অথবা username
                 * যেটি আছে সেটি ব্যবহার করবে।
                 */
                'name' =>
                    $user->name
                    ?? $user->username
                    ?? $user->email
                    ?? 'Logged-in User',

                'username' =>
                    $user->username
                    ?? $user->name
                    ?? $user->email
                    ?? 'Logged-in User',

                'email' =>
                    $user->email
                    ?? null,
            ]
            : null;

        /*
        |--------------------------------------------------------------------------
        | Order statuses
        |--------------------------------------------------------------------------
        */

        $statuses = [
            [
                'value' =>
                    Order::STATUS_PENDING,

                'label' =>
                    'Pending',
            ],
            [
                'value' =>
                    Order::STATUS_PREPARING,

                'label' =>
                    'Preparing',
            ],
            [
                'value' =>
                    Order::STATUS_READY,

                'label' =>
                    'Ready',
            ],
            [
                'value' =>
                    Order::STATUS_SERVED,

                'label' =>
                    'Served',
            ],
        ];

        return response()->json([
            'success' => true,

            'message' =>
                'Create order options loaded successfully.',

            'data' => [
                'tables' =>
                    $availableTables,

                /*
                 * Initially same available table list.
                 * Frontend will remove the selected
                 * primary table from this list.
                 */
                'merge_tables' =>
                    $availableTables,

                'menu_items' =>
                    $menuItems,

                'waiter' =>
                    $waiter,

                'statuses' =>
                    $statuses,

                'defaults' => [
                    'status' =>
                        Order::STATUS_PENDING,

                    'discount_amount' =>
                        0,

                    'tax_amount' =>
                        0,

                    'service_charge' =>
                        0,
                ],
            ],
        ]);
    }
}