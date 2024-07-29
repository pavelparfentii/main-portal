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
            $table->index('account_id');
            $table->index('telegram_id');
            $table->index('next_update_at');
        });

        Schema::table('account_referrals', function (Blueprint $table) {
            $table->index('account_id');
            $table->index('ref_subref_id');
        });

        Schema::table('account_task', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('is_done');
        });

        Schema::table('daily_rewards', function (Blueprint $table) {
            $table->index('account_id');
        });

        Schema::table('invites', function (Blueprint $table) {
            $table->index('whom_invited');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegrams', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropIndex(['telegram_id']);
            $table->dropIndex(['next_update_at']);
        });

        Schema::table('account_referrals', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
            $table->dropIndex(['ref_subref_id']);
        });

        Schema::table('account_task', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['is_done']);
        });

        Schema::table('daily_rewards', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
        });

        Schema::table('invites', function (Blueprint $table) {
            $table->dropIndex(['whom_invited']);
        });
    }
};
