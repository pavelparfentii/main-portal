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
        Schema::create('partner_n_f_t_s', function (Blueprint $table) {
            $table->id();
            $table->string('collection_name');
            $table->string('contract_address');
            $table->string('token_id');
            $table->decimal('reward_points', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_n_f_t_s');
    }
};
