<?php

namespace App\Console\Commands;

use App\Models\Account;
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
        $accounts = Account::with('animals', 'twitters')->get();

        foreach ($accounts as $account) {
            // Calculate the sum of points from digital animals
            $totalDigitalAnimalPoints = $account->animals->sum('points');

            // Update the account's total_points
            $account->total_points = $totalDigitalAnimalPoints;
            $account->save();

        }
    }
}
