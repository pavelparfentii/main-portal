<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Models\SafeSoul;
use App\Models\Twitter;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecountPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recount-points';

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
//        Account::query()->update(['total_points' => 0]);
//
//        $accounts = Account::cursor();
//
//        foreach ($accounts as $account) {
//            // Calculate the sum of points from digital animals
//            $totalDigitalAnimalPoints = $account->weeks()->where('week_number', Carbon::now()->subWeek()->format('W-Y'))->value('points');
////            dd($totalDigitalAnimalPoints);
//
//            // Update the account's total_points
//            $account->total_points = $totalDigitalAnimalPoints;
//            $account->save();
//
//        }

        $accounts = Account::cursor();

        $currentDate = Carbon::now();
        $startOfWeek = $currentDate->copy()->subWeek()->startOfWeek();
        $endOfWeek = $currentDate->copy()->subWeek()->endOfWeek();
//        $startOfWeek = Carbon::now()->subWeek();
        $currentWeekNumber = Carbon::now()->format('W-Y');
        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');



        foreach ($accounts as $account){

            $previousWeek = Week::where('account_id', $account->id)
                ->where('week_number', '=', $previousWeekNumber)
                ->where('active', true)
                ->first();

            if($previousWeek){
                $account->total_points += $previousWeek->points;
                $account->save();
                $previousWeek->update(['active'=>false]);
            }

            Week::getCurrentWeekForAccount($account);

        }

        $this->info('success');

    }
}
