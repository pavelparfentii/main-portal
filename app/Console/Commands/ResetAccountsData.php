<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetAccountsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-accounts-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset accounts data and set up weeks.';

    public function handle()
    {
        $this->info('Resetting accounts data...');

        // Disable foreign key checks to avoid constraint violations
       // DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Reset total_points for each Account to 0
        Account::query()->update(['role'=>null]);

        // Delete all Weeks and related data
        Week::all()->each(function ($week) {
            $week->safeSouls()->delete();
            $week->animals()->delete();
            $week->twitters()->delete();
            $week->delete();
        });

        // Re-enable foreign key checks
       // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create current and previous weeks for each Account
        Account::all()->each(function ($account) {

            $this->createPreviousWeekForAccount($account, Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek());
            $this->createWeekForAccount($account, Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek());
        });

        $this->info('Accounts data reset and weeks setup completed.');
    }

    protected function createWeekForAccount($account, $startOfWeek, $endOfWeek)
    {
        $weekNumber = $startOfWeek->weekOfYear . '-' . $startOfWeek->year;
        $account->weeks()->create([
            'week_number' => $weekNumber,
            'start_date' => $startOfWeek->toDateString(),
            'end_date' => $endOfWeek->toDateString(),
            'active' => true,
            'points' => 0,
            'claim_points' => 0,
            'claimed' => false,
            'claimed_points'=>0
        ]);
    }

    protected function createPreviousWeekForAccount($account, $startOfWeek, $endOfWeek)
    {
        $weekNumber = $startOfWeek->weekOfYear . '-' . $startOfWeek->year;
        $account->weeks()->create([
            'week_number' => $weekNumber,
            'start_date' => $startOfWeek->toDateString(),
            'end_date' => $endOfWeek->toDateString(),
            'active' => false,
            'points' => 0,
            'claim_points' => 0,
            'claimed' => true,
            'claimed_points'=>0
        ]);
    }
}
