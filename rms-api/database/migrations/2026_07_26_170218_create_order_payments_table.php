<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal(
                'amount',
                12,
                2
            );

            $table->string(
                'payment_method',
                50
            );

            $table->string(
                'reference'
            )->nullable();

            $table->text(
                'note'
            )->nullable();

            $table->foreignId(
                'received_by'
            )
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'order_id',
                'created_at',
            ]);

            $table->index(
                'payment_method'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'order_payments'
        );
    }
};