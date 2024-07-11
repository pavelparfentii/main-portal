<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecountPointsTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recount-points-telegram';

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
        $accounts = Account::on('pgsql_telegrams')->cursor();

        $previousWeekNumber = Carbon::now()->subWeek()->format('W-Y');

        foreach ($accounts as $account){

            $previousWeek = Week::on('pgsql_telegrams')
                ->where('account_id', $account->id)
                ->where('week_number', '=', $previousWeekNumber)
                ->where('active', true)
                ->first();

            if($previousWeek){

            // Old functionality
//                $account->total_points += $previousWeek->total_points;
//                $account->save();
                $previousWeek->update(['active'=>false]);
                $account->refresh();
            // Old functionality
//                DB::connection('pgsql_telegrams')
//                    ->table('account_farms')
//                    ->where('account_id', $account->id)
//                    ->update(['total_points' => $account->total_points]);

            }

            Week::getCurrentWeekForTelegramAccount($account);

        }

        $this->info('success');
    }
}
