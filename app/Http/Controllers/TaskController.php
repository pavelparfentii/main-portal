<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelperTelegram;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function getTaskList(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        $cacheKeyTasks = 'tasks_for_account_' . $account->id;
        $cacheKeyCompletedTasks = 'completed_tasks_for_account_' . $account->id;

//         Отримуємо задачі з кешу, якщо вони є, або завантажуємо і кешуємо їх
        $tasks = Cache::remember($cacheKeyTasks, now()->addHours(30), function () {
            return Task::on('pgsql_telegrams')
                ->with(['tags', 'parent.tags'])
                ->get();
        });

//        $tasks = Task::on('pgsql_telegrams')
//            ->with(['tags', 'parent.tags'])
//            ->get();

        $completedTasks = Cache::remember($cacheKeyCompletedTasks, now()->addHours(30), function () use ($account) {
            return $account->tasks()->wherePivot('is_done', true)->pluck('task_id')->toArray();
        });

//        $completedTasks = $account->tasks()->wherePivot('is_done', true)->pluck('task_id')->toArray();

        return response()->json($tasks->map(function ($task) use ($completedTasks) {
            $parents = [];
            $currentParent = $task->parent;

            while ($currentParent) {
                $parents[] = [
                    'id' => $currentParent->id,
                    'title' => $currentParent->title,
                    'tags' => $currentParent->tags->pluck('code')->toArray(),
                ];
                $currentParent = $currentParent->parent;
            }

            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'link' => $task->link,
                'points' => $this->formatPoints($task->points),
                'isDone' => in_array($task->id, $completedTasks),
                'tags' => $task->tags->pluck('code')->toArray(),
                'action' => $task->action,
                'parents' => $parents
            ];
        }));

    }

    public function updateTask(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>'task_id is required'], 400);
        }

        $task_id = $request->task_id;


        $task = Task::on('pgsql_telegrams')
            ->where('id', $task_id)
            ->first();

        if(!$task){
            return response()->json(['error' => 'Task not found'], 404);
        }

        //enable claim = true
        $account = AuthHelperTelegram::auth($request);

        $pivotRow = $account->tasks()->where('task_id', $task->id)->first();

        if ($pivotRow && $pivotRow->pivot->is_done) {
            return response()->json(['error' => 'Task is already done'], 400);
        }

        if($task->enable_claim) {

            $account->tasks()->attach($task->id, ['is_done' => true]);


        }else {

            $pivot = $account->tasks()->where('task_id', $task->id)->first();

            if(!$pivot->pivot->enable_claim) {

                return response()->json(['error' => 'Task is not available to claim'], 400);
            }

            $account->tasks()->updateExistingPivot($task->id, ['is_done' => true]);
        }


        $account->increment('total_points', $task->points);

        $exists = DB::connection('pgsql_telegrams')
            ->table('account_referrals')
            ->where('ref_subref_id', $account->id)
            ->exists();

        if ($exists) {
            DB::connection('pgsql_telegrams')
                ->table('account_referrals')
                ->where('ref_subref_id', $account->id)
                ->increment('income', $task->points);
        }

        //enable_claim false
//        cache

        $cacheKeyTasks = 'tasks_for_account_' . $account->id;
        $cacheKeyCompletedTasks = 'completed_tasks_for_account_' . $account->id;

        Cache::forget($cacheKeyTasks);
        Cache::forget($cacheKeyCompletedTasks);

        $tasks = Task::on('pgsql_telegrams')->with(['tags', 'parent.tags'])->get();

        $completedTasks = $account->tasks()->wherePivot('is_done', true)->pluck('task_id')->toArray();


        return response()->json($tasks->map(function ($task) use ($completedTasks) {
            $parents = [];
            $currentParent = $task->parent;

            while ($currentParent) {
                $parents[] = [
                    'id' => $currentParent->id,
                    'title' => $currentParent->title,
                    'tags' => $currentParent->tags->pluck('code')->toArray(),
                ];
                $currentParent = $currentParent->parent;
            }

            return [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'link' => $task->link,
                'points' => $task->points,
                'isDone' => in_array($task->id, $completedTasks),
                'tags' => $task->tags->pluck('code')->toArray(),
                'action' => $task->action,
                'parents' => $parents
            ];
        }));

    }

    public function checkTelegramChannelConnection(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        $taskId = $request->task_id;

        $validator = Validator::make($request->all(), [
            'task_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error'=>'task_id is required'], 400);
        }


        $arrayResponse = $this->taskChecking($taskId, $account);

        // dd($arrayResponse);

        return response()->json($arrayResponse['message'], $arrayResponse['code']);

    }

    private function checkExistingTask($account, $taskId, $bool)
    {
        $existingTask = $account->tasks()->where('task_id', $taskId)->first();

        if ($existingTask) {

            $account->tasks()->updateExistingPivot($taskId, ['enable_claim' => $bool]);
        } else {

            $account->tasks()->attach($taskId, ['enable_claim' => $bool]);
        }
    }

    private function taskChecking($taskId, $account): array
    {
        $task = Task::on('pgsql_telegrams')->where('id', $taskId)
            ->where('action', 'like','%check_%')
            ->first();



        if(!$task){

            $response = [
                'message' => ['error' => 'Task not found'],
                'code' => 200
            ];

            return $response;
        }


        if (str_contains($task->action, 'telegram')) {

            return $this->checkTelegramSubscribe($task, $account);

        }

        if (str_contains($task->action, 'friends')) {

            return $this->checkReferralCount($task, $account);

        }


        $response = [
            'message' => ['error' => 'Task not found'],
            'code' => 200
        ];

        return $response;

    }

    private function checkTelegramSubscribe($task, $account)
    {
        $chat_id = null;

        $taskId = $task->id;

        if (preg_match('/https:\/\/t\.me\/([^\/]+)/', $task->link, $matches)) {
            $chat_id = $matches[1];

        }

        $botToken = env('TELEGRAM_BOT');

        if(!$chat_id){

            $response = [
                'message' => ['status' => false],
                'code' => 200
            ];

            return $response;
        }

        $telegram_id = $account->telegram->telegram_id;

        $response = Http::post("https://api.telegram.org/bot$botToken/getChatMember?chat_id=@$chat_id&user_id=$telegram_id");

        $data = json_decode($response, true);

        if (!$data['ok']) {

            return [
                'message' => ['status' => false, 'error' => $data['description']],
                'code' => 200
            ];
        }

        if (is_array($data) && isset($data['result']) && is_array($data['result']) && isset($data['result']['status'])) {

            if ($data['result']['status'] !== 'left') {

                $this->checkExistingTask($account, $taskId, true);


                $response = [
                    'message' => ['status' => true],
                    'code' => 200
                ];

                return $response;

            }else{

                $this->checkExistingTask($account, $taskId, false);

                $response = [
                    'message' => ['status' => false],
                    'code' => 200
                ];

                return $response;
            }
        }else{
            $response = [
                'message' => ['status' => false],
                'code' => 200
            ];
            return $response;
        }
    }

    private function checkReferralCount($task, $account)
    {
        $invitedCount = $account->invitesSent()->count();


        if (str_contains($task->title, '5') && $invitedCount >= 5 ) {

            $response = [
                'message' => ['status' => true],
                'code' => 200
            ];

        } elseif (str_contains($task->title, '30') && $invitedCount >= 30 ) {

            $response = [
                'message' => ['status' => true],
                'code' => 200
            ];

        } elseif (str_contains($task->title, '100') && $invitedCount >= 100 ) {

            $response = [
                'message' => ['status' => true],
                'code' => 200
            ];

        } elseif (str_contains($task->title, '500') && $invitedCount >= 500) {

            $response = [
                'message' => ['status' => true],
                'code' => 200
            ];

        } else {

            $response = [
                'message' => ['status' => false],
                'code' => 200
            ];

            // return response()->json(['error' => 'Not enough friends to claim'], 400);
        }

        return $response;
    }

    public function formatPoints($points) {
        if ($points >= 1000) {
            return round($points / 1000, 1) . 'k';
        }
        return $points;
    }


}
