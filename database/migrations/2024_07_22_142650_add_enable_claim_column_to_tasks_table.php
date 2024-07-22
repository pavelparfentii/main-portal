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
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('enable_claim')->default(true);
        });

        Schema::table('account_task', function (Blueprint $table) {
            $table->boolean('enable_claim')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('enable_claim');
        });

        Schema::table('account_task', function (Blueprint $table) {
            $table->dropColumn('enable_claim');
        });
    }
};
