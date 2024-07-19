<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelperTelegram;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        if ($task) {
            $account = AuthHelperTelegram::auth($request);


            $pivotRow = $account->tasks()->where('task_id', $task->id)->first();


            if ($pivotRow && $pivotRow->pivot->is_done) {
                return response()->json(['error' => 'Task is already done'], 400);
            }

            $account->tasks()->attach($task->id, ['is_done' => true]);

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

//            DB::connection('pgsql_telegrams')
//                ->table('account_farms')
//                ->where('account_id', $account->id)
//                ->increment('daily_farm', $task->points);


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
        } else {
            return response()->json(['error' => 'Task not found'], 404);
        }


    }
}
