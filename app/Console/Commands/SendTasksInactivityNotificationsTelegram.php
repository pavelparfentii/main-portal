<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramTasksInactivityJob;
use App\Models\Account;
use Illuminate\Console\Command;

class SendTasksInactivityNotificationsTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-tasks-inactivity-notifications-telegram';

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
        $batchSize = 100;

        Account::on('pgsql_telegrams')
            ->join('telegrams', 'accounts.id', '=', 'telegrams.account_id')
            ->with('telegram')
            ->whereNotNull('telegrams.telegram_id')
            ->whereDoesntHave('tasks', function ($query) {
                $query->where('is_done', true);
            })
            ->groupBy('telegrams.telegram_id', 'telegrams.id', 'accounts.id')
            ->chunk($batchSize, function ($accountsWithoutDoneTasks) {

                foreach ($accountsWithoutDoneTasks as $account) {


                        $message = "GM💎 You’ve got some tasks waiting in the miniapp. Finish them up and scoop those Diamonds!";
                        $this->sendMessage($account->telegram_id, $message);


                }
            });


    }

    private function sendMessage($telegram_id, $message)
    {
        $delaySeconds = 3;

        SendTelegramTasksInactivityJob::dispatch($telegram_id, $message)
            ->delay(now()->addSeconds($delaySeconds))
            ->onQueue('telegram');
    }
}
