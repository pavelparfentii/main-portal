<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('wallet')->nullable();
            $table->string('twitter_username')->nullable();
            $table->string('twitter_id')->nullable();
            $table->string('discord_id')->nullable();
            $table->decimal('total_points', 10, 3)->default(0);
            $table->string('role')->nullable();
            $table->string('auth_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
