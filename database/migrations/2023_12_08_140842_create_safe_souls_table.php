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
        Schema::create('safe_souls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->string('period')->nullable();
            $table->boolean('re_count')->default(false);
            $table->decimal('points', 10, 3)->default(0);
            $table->text('comment')->nullable();
            $table->string('query_param')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safe_souls');
    }
};
