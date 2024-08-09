<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelperTelegram;
use App\Http\Resources\BetResource;
use App\Http\Resources\WinnerResource;
use App\Models\Account;
use App\Models\Bet;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WheelGameController extends Controller
{

    public function getGame(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);


        $gameLabel = 'wheel_game';

        $game = Game::on('pgsql_telegrams')
            ->where('game_label', $gameLabel)
            ->first();


        $currentRound = $game->rounds()->latest()->first();

        $bets = $currentRound->bets()->with(['account.telegram'])->get();

        $winner = $currentRound->winner_id ? Account::on('pgsql_telegrams')->with('telegram')->where('id', $currentRound->winner_id)->first() : null;

//        $betsCount = $bets->count();
//        $totalBetsSum = $bets->sum('amount');

        $betsEndTime = $currentRound->bets_end_time;
        $newGameStartTime = $currentRound->new_game_start_at;

        $totalAmountWon = $currentRound->total_amount;

        if ($account) {
           // $accountBet = $account->bets()->where('round_id', $currentRound->id)->sum('amount');

            return response()->json([
                'game'=>$game,
                'bets'=>BetResource::collection($bets),
//                'bets_count'=>$betsCount,
//                'total_bets_sum'=>$totalBetsSum,
//                'account_bet'=>$accountBet,
                'winner'=> $winner ? new WinnerResource($winner, $totalAmountWon) : null,
                'current_round'=>$currentRound->id,
                'round_status'=>$currentRound->status,
                'bets_end_time' => $betsEndTime ? $betsEndTime->toDateTimeString() : null,
                'new_game_start'=>$newGameStartTime ? $newGameStartTime->toDateTimeString() : null,
            ]);

        }elseif (!$account){

            return response()->json([
                'game'=>$game,
                'bets'=>$bets,
//                'bets_count'=>$betsCount,
//                'total_bets_sum'=>$totalBetsSum,
//                'account_bet'=>null,
                'winner'=> WinnerResource::collection($winner),
                'bets_end_time' => $betsEndTime ? $betsEndTime->toDateTimeString() : null,
                'new_game_start'=>$newGameStartTime ? $newGameStartTime->toDateTimeString() : null,
            ]);
        }
    }

    public function placeBet(Request $request)
    {
        $account = AuthHelperTelegram::auth($request);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.001'
        ]);

        if ($validator->fails()) {

            return response()->json(['error' => $validator->errors()], 422);

        }

        $gameLabel = 'wheel_game';

        $game = Game::on('pgsql_telegrams')
            ->where('game_label', $gameLabel)
            ->first();

        if ($account->total_points < $request->amount) {

            return response()->json(['error' => 'Insufficient funds'], 403);

        }

        $currentRound = $game->rounds()->where('status', 'waiting')->first();

        if (!$currentRound) {

            return response()->json(['error' => 'Round is over. Bets no more allowed'], 403);
        }

        $betsCount = $currentRound->bets->count();

        if($betsCount > 50){
            return response()->json(['error' => 'Round is over. Bets no more allowed'], 403);
        }

        $bet = Bet::on('pgsql_telegrams')
            ->where('account_id', $account->id)
            ->where('round_id', $currentRound->id)
            ->first();

        if(!$bet){
            $account->total_points -= $request->amount;
            $account->save();

            Bet::on('pgsql_telegrams')->create([
                'account_id' => $account->id,
                'round_id' => $currentRound->id,
                'amount' => $request->amount
            ]);

            $currentRound->increment('total_amount', $request->amount);

            return response()->json(['message' => 'Bet accepted'], 200);
        }
        return response()->json(['message' => 'Bet is already accepted'], 403);

    }
}
