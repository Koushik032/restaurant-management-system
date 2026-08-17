<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'order_kitchen_batches',
            function (Blueprint $table): void {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Parent Order
                |--------------------------------------------------------------------------
                */

                $table->foreignId('order_id')
                    ->constrained('orders')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Batch Number
                |--------------------------------------------------------------------------
                |
                | Same order:
                |
                | Batch 1 = original order
                | Batch 2 = first extension
                | Batch 3 = second extension
                |
                */

                $table->unsignedInteger('batch_no');

                /*
                |--------------------------------------------------------------------------
                | Kitchen Status
                |--------------------------------------------------------------------------
                |
                | accepted state is represented as:
                |
                | status = pending
                | chef_id != null
                |
                */

                $table->string(
                    'status',
                    30
                )->default('pending');

                /*
                |--------------------------------------------------------------------------
                | Assigned Chef
                |--------------------------------------------------------------------------
                */

                $table->foreignId('chef_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Kitchen Lifecycle
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'sent_to_kitchen_at'
                )->nullable();

                $table->timestamp(
                    'preparing_at'
                )->nullable();

                $table->timestamp(
                    'ready_at'
                )->nullable();

                $table->timestamp(
                    'served_at'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | One Batch Number Per Order
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'order_id',
                        'batch_no',
                    ],
                    'order_kitchen_batches_order_batch_unique'
                );

                /*
                |--------------------------------------------------------------------------
                | Kitchen Queue Index
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'status',
                        'chef_id',
                    ],
                    'order_kitchen_batches_status_chef_index'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Backfill Existing Orders
        |--------------------------------------------------------------------------
        |
        | আপনার database-এ already existing orders আছে।
        |
        | তাই existing প্রতিটি order-কে Batch #1 হিসেবে preserve করা হবে।
        |
        | এতে existing data হারাবে না এবং next migration-এ existing
        | order_items-কে এই Batch #1-এর সাথে attach করা যাবে।
        |
        */

        DB::table('orders')
            ->select([
                'id',
                'status',
                'chef_id',
                'sent_to_kitchen_at',
                'preparing_at',
                'ready_at',
                'served_at',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->orderBy('id')
            ->chunkById(
                200,
                function ($orders): void {
                    $rows = [];

                    foreach ($orders as $order) {
                        /*
                        |--------------------------------------------------------------------------
                        | Resolve Historical Kitchen Status
                        |--------------------------------------------------------------------------
                        |
                        | "completed" হলো billing/order finalization status।
                        |
                        | Kitchen-এর perspective থেকে completed order-এর
                        | latest original batch already served।
                        |
                        */

                        $batchStatus = match (
                            (string) $order->status
                        ) {
                            'completed' =>
                                'served',

                            'canceled' =>
                                'canceled',

                            'pending',
                            'preparing',
                            'ready',
                            'served' =>
                                (string) $order->status,

                            default =>
                                'pending',
                        };

                        $rows[] = [
                            'order_id' =>
                                (int) $order->id,

                            'batch_no' =>
                                1,

                            'status' =>
                                $batchStatus,

                            'chef_id' =>
                                $order->chef_id !== null
                                    ? (int) $order->chef_id
                                    : null,

                            'sent_to_kitchen_at' =>
                                $order->sent_to_kitchen_at,

                            'preparing_at' =>
                                $order->preparing_at,

                            'ready_at' =>
                                $order->ready_at,

                            'served_at' =>
                                $order->served_at,

                            'created_by' =>
                                $order->created_by !== null
                                    ? (int) $order->created_by
                                    : null,

                            'created_at' =>
                                $order->created_at,

                            'updated_at' =>
                                $order->updated_at,
                        ];
                    }

                    if ($rows !== []) {
                        DB::table(
                            'order_kitchen_batches'
                        )->insert(
                            $rows
                        );
                    }
                },
                'id'
            );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'order_kitchen_batches'
        );
    }
};