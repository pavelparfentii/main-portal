<?php

namespace App\Http\Controllers;

use App\Models\Telegram;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TelegramController extends Controller
{
    public function initiateSession(Request $request)
    {

        $hash = $request->get('hash');
        $id = $request->get('id');

        $validator = Validator::make($request->all(), [
            'hash' => 'required|string|max:255',
            'id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        Cache::put('Bearer ' . $hash.$id, 'Bearer ' . $hash.$id, now()->addMinutes(30));;


        return response()->json(['message' => 'Session initiated', 'key' => 'Bearer '. $hash.$id]);
    }

    public function getPoints(Request $request)
    {
        $telegram_id = $request->id;


        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $telegram = Telegram::where('telegram_id', $telegram_id)->first();

        if(isset($telegram)){
            return response()->json($telegram);
        }else{
            return response()->json(['message' => 'not found'], 204);
        }


    }

    public function updatePoints(Request $request)
    {
        $telegram_id = $request->get('id');

        $points = 0.5;

        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $telegram = Telegram::where('telegram_id', $telegram_id)->first();

        if(!$telegram){
            $telegram = Telegram::create([
                'telegram_id'=>$telegram_id,
                'points'=>$points,
                'next_update_at'=>now()->addHours(8),
            ]);

            return response()->json($telegram);
        }else{

            if(Carbon::parse($telegram->next_update_at)->isPast()){
                $telegram->increment('points', $points);
                $telegram->update(['next_update_at'=>now()->addHours(8)]);

                return response()->json($telegram);
            }else{
                return response()->json(['message' => 'not allowed to update'], 423);
            }
        }
    }
}
