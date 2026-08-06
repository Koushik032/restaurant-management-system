<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [

            [
                'name' =>
                    'Raw Materials',

                'description' =>
                    'Food ingredients and kitchen raw materials.',
            ],

            [
                'name' =>
                    'Salary',

                'description' =>
                    'Employee salary and wages.',
            ],

            [
                'name' =>
                    'Utility Bills',

                'description' =>
                    'Electricity, water, gas and internet bills.',
            ],

            [
                'name' =>
                    'Maintenance',

                'description' =>
                    'Repair and maintenance expenses.',
            ],

            [
                'name' =>
                    'Rent',

                'description' =>
                    'Restaurant rent and property expenses.',
            ],

            [
                'name' =>
                    'Transport',

                'description' =>
                    'Delivery, transport and travel expenses.',
            ],

            [
                'name' =>
                    'Marketing',

                'description' =>
                    'Advertising and promotional expenses.',
            ],

            [
                'name' =>
                    'Equipment',

                'description' =>
                    'Kitchen and restaurant equipment purchase.',
            ],

            [
                'name' =>
                    'Other',

                'description' =>
                    'Other miscellaneous expenses.',
            ],

        ];


        foreach (
            $categories
            as $category
        ) {

            ExpenseCategory::updateOrCreate(

                [
                    'name' =>
                        $category['name'],
                ],

                [

                    'description' =>
                        $category['description'],

                    'is_active' =>
                        true,

                ]

            );

        }
    }
}