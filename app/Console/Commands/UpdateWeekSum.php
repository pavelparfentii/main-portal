<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateWeekSum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-week-sum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

        $accountsWeekAgo = DB::table('accounts')
            ->leftJoin('weeks', function ($join) use ($previousWeekNumber) {
                $join->on('accounts.id', '=', 'weeks.account_id')
                    ->where('weeks.week_number', '=', $previousWeekNumber)
                    ->where('weeks.active', '=', false);
            })
            ->select('accounts.id',
                DB::raw('accounts.total_points - COALESCE(weeks.total_points, 0) as deducted_points'))
            ->orderByDesc('deducted_points')
            ->get();

        $accountsWithZeroPoints = $accountsWeekAgo->filter(function ($account) {
            return $account->deducted_points == 0;
        });


        $rank = 1;

        $lastPlaceRank = $accountsWithZeroPoints->count() + 1;

        foreach ($accountsWeekAgo as $account) {
            // Determine rank based on points
//            $weeks_ago_position = $account->week_points !== null ? $rank : $lastPlaceRank;
            $weeks_ago_position = $account->deducted_points !== null ? $rank : $lastPlaceRank;

//            var_dump($account->two_weeks_ago_position);

            // Update the previous week's position based on the two weeks ago position
            DB::table('accounts')
                ->where('id', $account->id)
//                ->where('week_number', $previousWeekNumber)
                ->update([
                    'previous_rank' => $weeks_ago_position,
                ]);

            if ($account->deducted_points !== null && $account->deducted_points != 0) {
                $rank++;
            }
        }

        $accounts = DB::table('accounts')
            ->select('accounts.id', 'accounts.total_points')
            ->orderByDesc('accounts.total_points')
            ->get();

        $accountsWithZeroPoints = $accounts->filter(function ($account) {
            return $account->total_points == 0;
        });

        $rank = 1;

        $lastPlaceRank = $accountsWithZeroPoints->count() + 1;

        foreach ($accounts as $account) {
            $position = $account->total_points !== 0 ? $rank : $lastPlaceRank;

            DB::table('accounts')
                ->where('id', $account->id)
//                ->where('week_number', $previousWeekNumber)
                ->update([
                    'current_rank' => $position,
                ]);

            if ($account->total_points !== null && $account->total_points != 0) {
                $rank++;
            }
        }
    }
}
