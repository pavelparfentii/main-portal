<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Traits\DigitalAnimalsTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DigitalAnimalsOwnersPointsCommand extends Command
{
    use DigitalAnimalsTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:digital-animals-owners-points-command';

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
        $accounts = DB::table('accounts')
            ->whereNotNull('wallet')
            ->join('account_farms', 'account_farms.account_id', '=', 'accounts.id')
            ->where('daily_farm', '=', 0.0)
            ->pluck('account_id');

        $holders = Account::whereIn('id', $accounts)->get();

        foreach ($holders as $holder){
            $winners = DB::table('farming_n_f_t_s')
                ->where('holder', $holder->wallet)
                ->get();

            if(count($winners)>0){
                // var_dump($holder);
                // dd('here');
                $farmPoints = DB::table('farming_n_f_t_s')
                        ->where('holder', $holder->wallet)
                        ->sum('farm_points_daily_total') ?? 0;
                var_dump($holder->wallet);
                var_dump($farmPoints);

                DB::table('account_farms')
                    ->where('account_id', $holder->id)
                    ->update(['daily_farm_last_update' => now(), 'daily_farm' => $farmPoints]);


            }
        }


//        $this->getOwners();
        $this->info('success');
    }
}
