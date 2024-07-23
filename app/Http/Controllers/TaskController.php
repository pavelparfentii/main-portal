<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelperTelegram;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function getTaskList(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

//        $tasks = $account->tasks()->with(['tags', 'parent.tags'])->get();

        $tasks = Task::on('pgsql_telegrams')
            ->with(['tags', 'parent.tags'])
            ->get();

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
        // account_task для дан таски і дан юзера проверить запись enable_claim = true
        //якшо true виконується як раніше, тілоьки без створення таск_аккаунт запису
        //якшо !task enable_claim false не приймається виконання not validated error

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

        $task = Task::on('pgsql_telegrams')->where('id', $taskId)
            ->where('action', 'like','%check_telegram_channel%')
            ->first();
        if($task){
            $telegram_id = $account->telegram->telegram_id;

            if(env('APP_ENV')==='production'){
                $chat_id = env('TELEGRAM_CHAT_PROD');
                $botToken = env('TELEGRAM_BOT');
            }else{
                $chat_id = env('TELEGRAM_CHAT');
                $botToken = env('TELEGRAM_BOT');
            }

            $response = Http::post("https://api.telegram.org/bot$botToken/getChatMember?chat_id=@$chat_id&user_id=$telegram_id");

            $data = json_decode($response, true);

            if (is_array($data) && isset($data['result']) && is_array($data['result']) && isset($data['result']['status'])) {

                if ($data['result']['status'] === 'member') {

                    $this->checkExistingTask($account, $taskId, true);

                    $message = ['status' => true];
                    $code = 200;
                    return response()->json($message, $code);
                }else{

                    $this->checkExistingTask($account, $taskId, false);

                    $message = ['status' => false];
                    $code = 200;
                    return response()->json($message, $code);
                }
            }

        }
        $message = ['error' => 'Task not found'];
        $code = 404;

        return response()->json($message, $code);

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
}
