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
            $table->dateTime('avatar_downloaded_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegrams', function (Blueprint $table) {
            $table->dropColumn('avatar_downloaded_at');
        });
    }
};
