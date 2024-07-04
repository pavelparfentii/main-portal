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

            $previousWeek = Week::where('account_id', $account->id)
                ->where('week_number', '=', $previousWeekNumber)
                ->where('active', true)
                ->first();

            if($previousWeek){

                $account->total_points += $previousWeek->total_points;
                $account->save();
                $previousWeek->update(['active'=>false]);
                $account->refresh();
            }

            Week::getCurrentWeekForTelegramAccount($account);

        }

        $this->info('success');
    }
}
