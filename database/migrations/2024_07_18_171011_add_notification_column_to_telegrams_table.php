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

            if (!Schema::hasColumn('telegrams', 'last_notification_at')) {
                $table->dateTime('last_notification_at')->nullable();
            }
            if (!Schema::hasColumn('telegrams', 'notification_stage')) {
                $table->integer('notification_stage')->default(0);
            }
            if (!Schema::hasColumn('telegrams', 'notification_sent')) {
                $table->boolean('notification_sent')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegrams', function (Blueprint $table) {
            $table->dropColumn('last_notification_at');
            $table->dropColumn('notification_stage');
            $table->dropColumn('notification_sent');
        });
    }
};
