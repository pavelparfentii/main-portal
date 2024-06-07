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
        Schema::table('invites', function (Blueprint $table) {
            $table->decimal('accumulated_income', 10, 3)->default(0);
            $table->dateTime('next_update_date')->nullable();

        });

        Schema::table('weeks', function (Blueprint $table) {
            $table->decimal('referrals_income', 10, 3)->default(0);

        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('referrals_claimed')->default(false);
            $table->dateTime('next_referrals_claim')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invites', function (Blueprint $table) {
            $table->dropColumn('accumulated_income');
            $table->dropColumn('next_update_date');

        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('referrals_claimed');
            $table->dropColumn('next_referrals_claim');
        });

        Schema::table('weeks', function (Blueprint $table) {
            $table->dropColumn('referrals_income');

        });
    }
};
