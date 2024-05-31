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
        Schema::create('farming_n_f_t_s', function (Blueprint $table) {
            $table->id();
            $table->string('contract_address');
            $table->string('holder');
            $table->string('token_id')->nullable();
            $table->integer('token_balance')->default(0);
            $table->dateTime('token_balance_last_update')->nullable();
            $table->decimal('item_points_daily', 10, 3)->default(0);
            $table->decimal('farm_points_daily_total', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farming_n_f_t_s');
    }
};
