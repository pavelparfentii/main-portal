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
        if (Schema::hasColumn('accounts', 'daily_farm')){
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('daily_farm');
            });
        }


        Schema::create('account_farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->decimal('daily_farm', 10, 3)->default(0);
            $table->dateTime('daily_farm_last_update')->nullable();
            $table->decimal('total_points', 13, 6)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_farms');
    }
};
