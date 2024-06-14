<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\DigitalAnimal;
use App\Models\SafeSoul;
use App\Models\Twitter;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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
                $account->total_points += $previousWeek->total_points;
                $account->save();
                $previousWeek->update(['active'=>false]);

                DB::table('account_farms')
                    ->where('account_id', $account->id)
                    ->update(['total_points' => $account->total_points]);
            }

            Week::getCurrentWeekForAccount($account);

        }

        $this->info('success');

    }
}
