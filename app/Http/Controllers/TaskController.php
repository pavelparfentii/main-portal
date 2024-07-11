<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelperTelegram;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function getTaskList(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        $tasks = $account->tasks()->with(['tags', 'parent.tags'])->get();

        return response()->json($tasks->map(function ($task) {
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
                'isDone' => $task->pivot->is_done,
                'tags' => $task->tags->pluck('code')->toArray(),
                'action' => $task->action,
                'parents' => $parents
            ];
        }));

//        return response()->json($tasks->map(function ($task) {
//            return [
//                'id' => $task->id,
//                'title' => $task->title,
//                'description' => $task->description,
//                'link' => $task->link,
//                'points' => $task->points,
//                'isDone' => $task->pivot->is_done,
//                'tag' => $task->tags()->get(),
//                'action' => $task->action,
//                'parents' => $task->parent ? [
//                    'id' => $task->parent->id,
//                    'title' => $task->parent->title,
//                    'tags' => $task->parent->tags()->get(),
//                ] : null,
//            ];
//        }));
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

        if($task){
            $account = AuthHelperTelegram::auth($request);

            $pivotRow = $account->tasks()->where('task_id', $task->id)->first();

            if ($pivotRow && $pivotRow->pivot->is_done) {
                return response()->json(['error' => 'Task is already done'], 400);
            }

            $account->tasks()->updateExistingPivot($task->id, ['is_done' => true]);

            $account->total_points += $task->points;
            $account->save();

            $tasks = $account->tasks()->with(['tags', 'parent.tags'])->get();

            return response()->json($tasks->map(function ($task) {
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
                    'isDone' => $task->pivot->is_done,
                    'tags' => $task->tags->pluck('code')->toArray(),
                    'action' => $task->action,
                    'parents' => $parents
                ];
            }));
        }else{
            return response()->json(['error' => 'Task not found'], 404);
        }


    }
}
