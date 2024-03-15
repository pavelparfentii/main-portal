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
        Schema::create('weeks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
//            $table->foreignId('account_id')->constrained()->onDelete('set null');
            $table->string('week_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('active')->default(true);
            $table->decimal('points', 10, 3)->default(0); // Sum of all points categories for the week
            $table->decimal('claim_points', 10, 3)->default(0);
            $table->boolean('claimed')->default(false);
            $table->timestamps();
        });

        Schema::table('safe_souls', function (Blueprint $table) {
            $table->unsignedBigInteger('week_id')->nullable();
            $table->foreign('week_id')->references('id')->on('weeks')->onDelete('set null');
//            $table->foreignId('week_id')->after('id')->constrained('weeks')->onDelete('set null');
            $table->decimal('claim_points', 10, 3)->default(0);
        });

        Schema::table('digital_animals', function (Blueprint $table) {
            $table->unsignedBigInteger('week_id')->nullable();
            $table->foreign('week_id')->references('id')->on('weeks')->onDelete('set null');
            $table->decimal('claim_points', 10, 3)->default(0);
        });

        Schema::table('digital_games', function (Blueprint $table) {
            $table->unsignedBigInteger('week_id')->nullable();
            $table->foreign('week_id')->references('id')->on('weeks')->onDelete('set null');
            $table->decimal('claim_points', 10, 3)->default(0);
        });

        Schema::table('digital_souls', function (Blueprint $table) {
            $table->unsignedBigInteger('week_id')->nullable();
            $table->foreign('week_id')->references('id')->on('weeks')->onDelete('set null');
            $table->decimal('claim_points', 10, 3)->default(0);
        });

        Schema::table('twitters', function (Blueprint $table) {
            $table->foreignId('week_id')->after('id')->constrained('weeks')->onDelete('set null');
            $table->decimal('claim_points', 10, 3)->default(0);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedBigInteger('week_id')->nullable();
            $table->foreign('week_id')->references('id')->on('weeks')->onDelete('set null');
            $table->decimal('claim_points', 10, 3)->default(0);
        });
        Schema::table('generals', function (Blueprint $table) {
            $table->unsignedBigInteger('week_id')->nullable();
            $table->foreign('week_id')->references('id')->on('weeks')->onDelete('set null');
            $table->decimal('claim_points', 10, 3)->default(0);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {


        Schema::table('safe_souls', function (Blueprint $table) {
            $table->dropForeign(['week_id']); // Drop foreign key constraint
            $table->dropColumn('week_id'); // Then drop the column
            $table->dropColumn('claim_points');
        });

        Schema::table('digital_animals', function (Blueprint $table) {
            $table->dropForeign(['week_id']); // Drop foreign key constraint
            $table->dropColumn('week_id'); // Then drop the column
            $table->dropColumn('claim_points');
        });

        Schema::table('digital_games', function (Blueprint $table) {
            $table->dropForeign(['week_id']); // Drop foreign key constraint
            $table->dropColumn('week_id'); // Then drop the column
            $table->dropColumn('claim_points');
        });

        Schema::table('digital_souls', function (Blueprint $table) {
            $table->dropForeign(['week_id']); // Drop foreign key constraint
            $table->dropColumn('week_id'); // Then drop the column
            $table->dropColumn('claim_points');
        });

        Schema::table('twitters', function (Blueprint $table) {
            $table->dropForeign(['week_id']); // Drop foreign key constraint
            $table->dropColumn('week_id'); // Then drop the column
            $table->dropColumn('claim_points');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['week_id']); // Drop foreign key constraint
            $table->dropColumn('week_id'); // Then drop the column
            $table->dropColumn('claim_points');
        });

        Schema::table('generals', function (Blueprint $table) {
            $table->dropForeign(['week_id']); // Drop foreign key constraint
            $table->dropColumn('week_id'); // Then drop the column
            $table->dropColumn('claim_points');
        });

        Schema::dropIfExists('weeks');
    }
};
