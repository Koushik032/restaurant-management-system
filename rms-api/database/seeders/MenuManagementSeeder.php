<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuManagementSeeder extends Seeder
{
    /**
     * Seed the menu management module.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedMenuCategoriesAndItems();
            $this->seedAddOns();
        });
    }

    /**
     * Create menu categories, menu items and variants.
     */
    private function seedMenuCategoriesAndItems(): void
    {
        $menuData = [
            [
                'category' => [
                    'category_name' => 'Burgers',
                    'description' => 'Freshly prepared burgers with different fillings and flavours.',
                    'is_available' => true,
                    'display_order' => 1,
                ],

                'items' => [
                    [
                        'menu_name' => 'Classic Chicken Burger',
                        'item_type' => 'regular',
                        'price' => 250,
                        'ingredients' => 'Chicken patty, lettuce, tomato, onion, mayonnaise and burger bun.',
                        'description' => 'A classic chicken burger prepared with a juicy chicken patty.',
                        'image_path' => null,
                        'preparation_time' => 15,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Single Patty',
                                'price' => 250,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Double Patty',
                                'price' => 350,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Beef Cheese Burger',
                        'item_type' => 'regular',
                        'price' => 320,
                        'ingredients' => 'Beef patty, cheese, lettuce, onion, pickles, sauce and burger bun.',
                        'description' => 'A juicy beef burger served with melted cheese.',
                        'image_path' => null,
                        'preparation_time' => 18,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Regular',
                                'price' => 320,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Double Beef',
                                'price' => 450,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Crispy Chicken Burger',
                        'item_type' => 'regular',
                        'price' => 280,
                        'ingredients' => 'Crispy chicken fillet, lettuce, mayonnaise, cheese and burger bun.',
                        'description' => 'Crispy fried chicken fillet served inside a soft burger bun.',
                        'image_path' => null,
                        'preparation_time' => 17,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [],
                    ],
                ],
            ],

            [
                'category' => [
                    'category_name' => 'Pizza',
                    'description' => 'Freshly baked pizzas with a variety of toppings.',
                    'is_available' => true,
                    'display_order' => 2,
                ],

                'items' => [
                    [
                        'menu_name' => 'Chicken Cheese Pizza',
                        'item_type' => 'regular',
                        'price' => 450,
                        'ingredients' => 'Pizza dough, chicken, mozzarella cheese, capsicum, onion and pizza sauce.',
                        'description' => 'A delicious chicken pizza topped with mozzarella cheese.',
                        'image_path' => null,
                        'preparation_time' => 25,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Small',
                                'price' => 450,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Medium',
                                'price' => 650,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Large',
                                'price' => 850,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Beef Pepperoni Pizza',
                        'item_type' => 'regular',
                        'price' => 520,
                        'ingredients' => 'Pizza dough, beef pepperoni, mozzarella cheese, olives and pizza sauce.',
                        'description' => 'Classic beef pepperoni pizza with melted mozzarella cheese.',
                        'image_path' => null,
                        'preparation_time' => 27,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [
                            [
                                'variant_name' => 'Small',
                                'price' => 520,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Medium',
                                'price' => 720,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Large',
                                'price' => 920,
                                'is_available' => true,
                            ],
                        ],
                    ],
                ],
            ],

            [
                'category' => [
                    'category_name' => 'Rice and Biryani',
                    'description' => 'Traditional rice, biryani and complete meal dishes.',
                    'is_available' => true,
                    'display_order' => 3,
                ],

                'items' => [
                    [
                        'menu_name' => 'Chicken Biryani',
                        'item_type' => 'regular',
                        'price' => 280,
                        'ingredients' => 'Basmati rice, chicken, potato, spices and fried onion.',
                        'description' => 'Traditional aromatic chicken biryani.',
                        'image_path' => null,
                        'preparation_time' => 20,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Half',
                                'price' => 180,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Full',
                                'price' => 280,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Beef Tehari',
                        'item_type' => 'regular',
                        'price' => 320,
                        'ingredients' => 'Aromatic rice, beef, mustard oil, green chilli and spices.',
                        'description' => 'Traditional beef tehari prepared with aromatic spices.',
                        'image_path' => null,
                        'preparation_time' => 20,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [],
                    ],

                    [
                        'menu_name' => 'Family Rice Set',
                        'item_type' => 'set_meal',
                        'price' => 1250,
                        'ingredients' => 'Fried rice, chicken curry, mixed vegetables, salad and soft drinks.',
                        'description' => 'A complete family set meal suitable for four people.',
                        'image_path' => null,
                        'preparation_time' => 35,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'For 4 Persons',
                                'price' => 1250,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'For 6 Persons',
                                'price' => 1750,
                                'is_available' => true,
                            ],
                        ],
                    ],
                ],
            ],

            [
                'category' => [
                    'category_name' => 'Fast Food',
                    'description' => 'Quick snacks, fried items and light meals.',
                    'is_available' => true,
                    'display_order' => 4,
                ],

                'items' => [
                    [
                        'menu_name' => 'French Fries',
                        'item_type' => 'regular',
                        'price' => 150,
                        'ingredients' => 'Potato, salt and seasoning.',
                        'description' => 'Golden and crispy French fries.',
                        'image_path' => null,
                        'preparation_time' => 10,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [
                            [
                                'variant_name' => 'Regular',
                                'price' => 150,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Large',
                                'price' => 220,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Chicken Wings',
                        'item_type' => 'regular',
                        'price' => 300,
                        'ingredients' => 'Chicken wings, spices and special sauce.',
                        'description' => 'Crispy chicken wings served with special sauce.',
                        'image_path' => null,
                        'preparation_time' => 18,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => '6 Pieces',
                                'price' => 300,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => '12 Pieces',
                                'price' => 550,
                                'is_available' => true,
                            ],
                        ],
                    ],
                ],
            ],

            [
                'category' => [
                    'category_name' => 'Combos',
                    'description' => 'Value combo offers containing multiple menu items.',
                    'is_available' => true,
                    'display_order' => 5,
                ],

                'items' => [
                    [
                        'menu_name' => 'Burger Combo',
                        'item_type' => 'combo',
                        'price' => 420,
                        'ingredients' => 'Chicken burger, French fries and soft drink.',
                        'description' => 'A complete burger combo with fries and a soft drink.',
                        'image_path' => null,
                        'preparation_time' => 20,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Regular Drink',
                                'price' => 420,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Large Drink',
                                'price' => 470,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Pizza Combo',
                        'item_type' => 'combo',
                        'price' => 750,
                        'ingredients' => 'Medium chicken pizza, French fries and two soft drinks.',
                        'description' => 'Pizza combo suitable for two people.',
                        'image_path' => null,
                        'preparation_time' => 30,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [],
                    ],

                    [
                        'menu_name' => 'Student Combo',
                        'item_type' => 'combo',
                        'price' => 299,
                        'ingredients' => 'Mini burger, French fries and soft drink.',
                        'description' => 'An affordable combo specially designed for students.',
                        'image_path' => null,
                        'preparation_time' => 15,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [],
                    ],
                ],
            ],

            [
                'category' => [
                    'category_name' => 'Set Meals',
                    'description' => 'Complete set meals for individual customers and groups.',
                    'is_available' => true,
                    'display_order' => 6,
                ],

                'items' => [
                    [
                        'menu_name' => 'Chicken Set Meal',
                        'item_type' => 'set_meal',
                        'price' => 350,
                        'ingredients' => 'Fried rice, fried chicken, vegetables and salad.',
                        'description' => 'A complete chicken set meal for one person.',
                        'image_path' => null,
                        'preparation_time' => 22,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Regular',
                                'price' => 350,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'With Soft Drink',
                                'price' => 400,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Beef Set Meal',
                        'item_type' => 'set_meal',
                        'price' => 420,
                        'ingredients' => 'Fried rice, beef curry, mixed vegetables and salad.',
                        'description' => 'A complete beef set meal for one person.',
                        'image_path' => null,
                        'preparation_time' => 25,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [],
                    ],
                ],
            ],

            [
                'category' => [
                    'category_name' => 'Beverages',
                    'description' => 'Cold drinks, juices, tea and coffee.',
                    'is_available' => true,
                    'display_order' => 7,
                ],

                'items' => [
                    [
                        'menu_name' => 'Soft Drink',
                        'item_type' => 'regular',
                        'price' => 60,
                        'ingredients' => null,
                        'description' => 'Chilled carbonated soft drink.',
                        'image_path' => null,
                        'preparation_time' => 2,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [
                            [
                                'variant_name' => '250 ml',
                                'price' => 60,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => '500 ml',
                                'price' => 100,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => '1 Litre',
                                'price' => 180,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Fresh Lemon Juice',
                        'item_type' => 'regular',
                        'price' => 120,
                        'ingredients' => 'Fresh lemon, sugar, water and ice.',
                        'description' => 'Refreshing freshly prepared lemon juice.',
                        'image_path' => null,
                        'preparation_time' => 5,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [],
                    ],

                    [
                        'menu_name' => 'Cold Coffee',
                        'item_type' => 'regular',
                        'price' => 180,
                        'ingredients' => 'Coffee, milk, sugar, ice and cream.',
                        'description' => 'Chilled creamy coffee.',
                        'image_path' => null,
                        'preparation_time' => 8,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Regular',
                                'price' => 180,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'With Ice Cream',
                                'price' => 230,
                                'is_available' => true,
                            ],
                        ],
                    ],
                ],
            ],

            [
                'category' => [
                    'category_name' => 'Desserts',
                    'description' => 'Sweet dishes and desserts.',
                    'is_available' => true,
                    'display_order' => 8,
                ],

                'items' => [
                    [
                        'menu_name' => 'Chocolate Brownie',
                        'item_type' => 'regular',
                        'price' => 180,
                        'ingredients' => 'Chocolate, flour, butter, sugar and egg.',
                        'description' => 'Rich chocolate brownie served warm.',
                        'image_path' => null,
                        'preparation_time' => 8,
                        'is_available' => true,
                        'is_featured' => true,

                        'variants' => [
                            [
                                'variant_name' => 'Regular',
                                'price' => 180,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'With Ice Cream',
                                'price' => 250,
                                'is_available' => true,
                            ],
                        ],
                    ],

                    [
                        'menu_name' => 'Vanilla Ice Cream',
                        'item_type' => 'regular',
                        'price' => 130,
                        'ingredients' => 'Milk, cream, sugar and vanilla.',
                        'description' => 'Creamy vanilla flavoured ice cream.',
                        'image_path' => null,
                        'preparation_time' => 3,
                        'is_available' => true,
                        'is_featured' => false,

                        'variants' => [
                            [
                                'variant_name' => 'One Scoop',
                                'price' => 130,
                                'is_available' => true,
                            ],
                            [
                                'variant_name' => 'Two Scoops',
                                'price' => 220,
                                'is_available' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($menuData as $categoryData) {
            $categoryAttributes = $categoryData['category'];

            $category = MenuCategory::withTrashed()->updateOrCreate(
                [
                    'category_name' => $categoryAttributes['category_name'],
                ],
                $categoryAttributes
            );

            if ($category->trashed()) {
                $category->restore();
            }

            foreach ($categoryData['items'] as $itemData) {
                $variants = $itemData['variants'] ?? [];

                unset($itemData['variants']);

                $menuItem = MenuItem::withTrashed()->updateOrCreate(
                    [
                        'menu_category_id' => $category->id,
                        'menu_name' => $itemData['menu_name'],
                    ],
                    array_merge(
                        $itemData,
                        [
                            'menu_category_id' => $category->id,
                        ]
                    )
                );

                if ($menuItem->trashed()) {
                    $menuItem->restore();
                }

                foreach ($variants as $variantData) {
                    $variant = MenuItemVariant::withTrashed()->updateOrCreate(
                        [
                            'menu_item_id' => $menuItem->id,
                            'variant_name' => $variantData['variant_name'],
                        ],
                        array_merge(
                            $variantData,
                            [
                                'menu_item_id' => $menuItem->id,
                            ]
                        )
                    );

                    if ($variant->trashed()) {
                        $variant->restore();
                    }
                }
            }
        }
    }


    /**
     * Create add-ons without add-on categories.
     */
    private function seedAddOns(): void
    {
        $addOns = [
            [
                'add_on_name' => 'Extra Cheese',
                'price' => 50,
                'description' => 'One additional cheese slice.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Chicken Patty',
                'price' => 100,
                'description' => 'One additional chicken patty.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Beef Patty',
                'price' => 140,
                'description' => 'One additional beef patty.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Sauce',
                'price' => 30,
                'description' => 'Additional serving of special sauce.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Mozzarella',
                'price' => 100,
                'description' => 'Additional mozzarella cheese topping.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Chicken',
                'price' => 120,
                'description' => 'Additional chicken topping.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Beef Pepperoni',
                'price' => 150,
                'description' => 'Additional beef pepperoni topping.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Black Olives',
                'price' => 60,
                'description' => 'Additional sliced black olives.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Rice',
                'price' => 80,
                'description' => 'One additional serving of rice.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Fried Egg',
                'price' => 40,
                'description' => 'One fried egg.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Chicken Piece',
                'price' => 120,
                'description' => 'One additional chicken piece.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Beef Portion',
                'price' => 160,
                'description' => 'One additional beef portion.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Salad',
                'price' => 50,
                'description' => 'Fresh mixed salad.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Ice',
                'price' => 10,
                'description' => 'Additional ice.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Extra Sugar',
                'price' => 10,
                'description' => 'Additional sugar.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Ice Cream Scoop',
                'price' => 70,
                'description' => 'One additional scoop of ice cream.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Whipped Cream',
                'price' => 50,
                'description' => 'Additional whipped cream topping.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Chocolate Sauce',
                'price' => 40,
                'description' => 'Additional chocolate sauce.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Vanilla Ice Cream Scoop',
                'price' => 70,
                'description' => 'One scoop of vanilla ice cream.',
                'is_available' => true,
            ],
            [
                'add_on_name' => 'Caramel Sauce',
                'price' => 40,
                'description' => 'Additional caramel sauce.',
                'is_available' => true,
            ],
        ];

        foreach ($addOns as $addOnData) {
            $addOn = AddOn::withTrashed()->updateOrCreate(
                [
                    'add_on_name' => $addOnData['add_on_name'],
                ],
                $addOnData
            );

            if ($addOn->trashed()) {
                $addOn->restore();
            }
        }
    }
}