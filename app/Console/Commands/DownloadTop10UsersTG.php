<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Invite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DownloadTop10UsersTG extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-top10-users-t-g';

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

//        $topAccounts = Account::on('pgsql_telegrams')
//            ->with('telegram')
//            ->orderBy('total_points', 'desc')
//            ->limit(10)
//            ->get();
//
//// Створити масив для зберігання даних
//        $csvData = [];
//
//// Додати заголовки колонок
//        $csvData[] = ['Total Points', 'Created At', 'Account ID', 'First Name', 'Last Name', 'Telegram ID', 'Task ID', 'Task Title', 'Is Done'];
//
//// Отримати таски для кожного акаунта і додати до масиву
//        $topAccounts->each(function ($account) use (&$csvData) {
//            $tasks = DB::connection('pgsql_telegrams')->table('tasks')
//                ->leftJoin('account_task', function ($join) use ($account) {
//                    $join->on('tasks.id', '=', 'account_task.task_id')
//                        ->where('account_task.account_id', '=', $account->id);
//                })
//                ->leftJoin('telegrams', 'telegrams.account_id', '=', 'account_task.account_id')
//                ->select(
//                    DB::raw("'{$account->total_points}' as total_points"),
//                    DB::raw("'{$account->created_at}' as created_at"),
//                    'telegrams.account_id',
//                    'telegrams.first_name',
//                    'telegrams.last_name',
//                    'telegrams.telegram_id',
//                    'tasks.id',
//                    'tasks.title',
//                    DB::raw('COALESCE(account_task.is_done, false) as is_done')
//                )
//                ->get();
//
//            // Додати таски до масиву CSV даних
//            foreach ($tasks as $task) {
//                $csvData[] = [
//                    $task->total_points,
//                    $task->created_at,
//                    $task->account_id,
//                    $task->first_name,
//                    $task->last_name,
//                    $task->telegram_id,
//                    $task->id,
//                    $task->title,
//                    $task->is_done
//                ];
//            }
//        });
//
//// Створити і відкрити тимчасовий CSV файл для запису
//        $filePath = storage_path('app/public/top_accounts_with_tasks.csv');
//        $csvFile = fopen($filePath, 'w');
//
//// Записати дані у CSV файл
//        foreach ($csvData as $row) {
//            fputcsv($csvFile, $row);
//        }
//
//        fclose($csvFile);



        //// Inited
        $invited = Invite::on('pgsql_telegrams')
            ->where('invited_by', 9)
            ->pluck('whom_invited')
            ->toArray();

        $InvitedAccounts = Account::on('pgsql_telegrams')
            ->with('telegram')
            ->whereIn('id', $invited)
            ->get();

        $csvData = [];

// Додати заголовки колонок
        $csvData[] = ['ID', 'First Name', 'Last Name', 'Telegram ID', 'Total Points', 'Created At',];

        foreach ($InvitedAccounts as $account) {
        $csvData[] = [
            $account->id,
            $account->telegram->first_name ?? '', // Використовуємо null-coalescing оператор для уникнення помилок
            $account->telegram->last_name ?? '',
            $account->telegram->telegram_id ?? '',
            $account->total_points,
            $account->created_at,
        ];
    }

        // Створити і відкрити тимчасовий CSV файл для запису
        $filePath = storage_path('app/public/invited_accounts.csv');
        $csvFile = fopen($filePath, 'w');

        // Записати дані у CSV файл
        foreach ($csvData as $row) {
            fputcsv($csvFile, $row);
        }

        // Закрити файл
        fclose($csvFile);



    }
}
