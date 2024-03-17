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
//        $accounts = Account::with('animals', 'twitters')->get();
//
//        foreach ($accounts as $account) {
//            // Calculate the sum of points from digital animals
//            $totalDigitalAnimalPoints = $account->animals->sum('points');
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
                ->where('start_date', '<=', $startOfWeek)
                ->where('end_date', '>=', $endOfWeek)
                ->where('active', false)
                ->first();


            if (!$previousWeek) {
                $currentDate = Carbon::now();
                $previousWeek = $account->weeks()->create([
                    'week_number' => $previousWeekNumber,
                    'start_date' => $startOfWeek->toDateString(),
                    'end_date' => $endOfWeek->toDateString(),
                    'active' => false,
                    'points' => $account->total_points,
                    'claim_points' => 0,
                    'claimed'=>true
                ]);

                SafeSoul::where('account_id', $account->id)->update(['week_id'=>$previousWeek->id]);
                DigitalAnimal::where('account_id', $account->id)->update(['week_id'=>$previousWeek->id]);
                Twitter::where('account_id', $account->id)->update(['week_id'=>$previousWeek->id]);
            }
        }

        $this->info('success');

    }
}
