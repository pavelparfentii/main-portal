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
        Schema::table('telegrams', function (Blueprint $table) {
            $table->dateTime('last_notification_at')->nullable();
            $table->boolean('notification_sent')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegrams', function (Blueprint $table) {
            //
        });
    }
};
