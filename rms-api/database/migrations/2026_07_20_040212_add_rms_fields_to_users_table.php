<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->string('username', 50)
                ->nullable()
                ->unique()
                ->after('name');

            $table->boolean('is_active')
                ->default(true)
                ->after('password');

            $table->timestamp('last_login_at')
                ->nullable()
                ->after('is_active');

            $table->timestamp('last_logout_at')
                ->nullable()
                ->after('last_login_at');

            $table->unsignedSmallInteger('failed_login_attempts')
                ->default(0)
                ->after('last_logout_at');

            $table->timestamp('blocked_at')
                ->nullable()
                ->after('failed_login_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);

            $table->dropColumn([
                'role_id',
                'username',
                'is_active',
                'last_login_at',
                'last_logout_at',
                'failed_login_attempts',
                'blocked_at',
            ]);
        });
    }
};