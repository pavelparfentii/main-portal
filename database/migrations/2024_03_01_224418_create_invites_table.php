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
        Schema::create('invites', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('invited_by');
            $table->foreign('invited_by')->references('id')->on('accounts')->onDelete('cascade');
            $table->string('inviter_wallet')->nullable();

            $table->unsignedBigInteger('whom_invited');
            $table->string('invitee_wallet');
            $table->foreign('whom_invited')->references('id')->on('accounts')->onDelete('cascade');

            $table->foreignId('code_id')->constrained()->onDelete('cascade');
            $table->string('used_code')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
