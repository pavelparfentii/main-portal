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
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('daily_farm', 10, 3)->default(0);
            $table->bigInteger('current_rank')->nullable();
            $table->bigInteger('previous_rank')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('daily_farm');
            $table->dropColumn('current_rank');
            $table->dropColumn('previous_rank');
        });
    }
};
