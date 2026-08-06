<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists(
            'add_on_categories'
        );
    }

    public function down(): void
    {
        //
    }
};