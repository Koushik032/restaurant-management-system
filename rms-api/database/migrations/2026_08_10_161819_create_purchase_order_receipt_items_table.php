<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'purchase_order_receipt_items',
            function (Blueprint $table): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Receipt
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'purchase_order_receipt_id'
                )
                    ->constrained(
                        'purchase_order_receipts'
                    )
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Purchase Order Item
                |--------------------------------------------------------------------------
                |
                | Receipt history থাকার পরে original PO item hard-delete
                | হতে দেওয়া হবে না।
                |
                */

                $table->foreignId(
                    'purchase_order_item_id'
                )
                    ->constrained(
                        'purchase_order_items'
                    )
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Raw Material
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'raw_material_id'
                )
                    ->constrained(
                        'raw_materials'
                    )
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Item Snapshot
                |--------------------------------------------------------------------------
                |
                | Future-এ raw material/item name পরিবর্তন হলেও
                | historical GRN-এর original information থাকবে।
                |
                */

                $table->string(
                    'item_name',
                    255
                );


                $table->string(
                    'unit',
                    30
                );


                /*
                |--------------------------------------------------------------------------
                | Received Quantity
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'quantity',
                    14,
                    2
                );


                /*
                |--------------------------------------------------------------------------
                | Receive Cost
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'unit_cost',
                    14,
                    4
                );


                $table->decimal(
                    'total_cost',
                    18,
                    4
                );


                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )
                    ->nullable();


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Unique
                |--------------------------------------------------------------------------
                |
                | একই GRN-এর মধ্যে একই PO item দুইবার থাকতে পারবে না।
                |
                */

                $table->unique(
                    [
                        'purchase_order_receipt_id',
                        'purchase_order_item_id',
                    ],
                    'po_receipt_item_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'raw_material_id'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'purchase_order_receipt_items'
        );
    }
};