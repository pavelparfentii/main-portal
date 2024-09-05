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
        Schema::create('twitter_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('retweets')->default(0);
            $table->bigInteger('quotes')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('tagged_post')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twitter_actions');
    }
};
