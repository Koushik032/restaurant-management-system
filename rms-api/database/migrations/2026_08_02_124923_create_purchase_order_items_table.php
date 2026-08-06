<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


public function up(): void
{


Schema::create(
'purchase_order_items',
function(Blueprint $table){


$table->id();



$table->foreignId(
    'purchase_order_id'
)
->constrained(
    'purchase_orders'
)
->cascadeOnDelete();





/*
|--------------------------------------------------------------------------
| Item Information
|--------------------------------------------------------------------------
*/


$table->string(
    'item_name'
);



$table->string(
    'unit',
    30
);



$table->decimal(
    'quantity',
    12,
    2
)
->default(0);



$table->decimal(
    'received_quantity',
    12,
    2
)
->default(0);



$table->decimal(
    'unit_price',
    12,
    2
)
->default(0);



$table->decimal(
    'total_price',
    14,
    2
)
->default(0);




$table->timestamps();


});

}



public function down(): void
{

Schema::dropIfExists(
'purchase_order_items'
);

}


};