<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('provider_id')->nullable()->unique();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('avatar')->nullable();
            $table->string('password')->nullable();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->uuid('session_id')->primary();
            $table->text('user_agent')->nullable();
            $table->text('refresh_token')->nullable();
            $table->bigInteger('last_login')->nullable();
            $table->timestamps();

            $table->foreignUuid('user_id')->constrained('users',"user_id")->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
