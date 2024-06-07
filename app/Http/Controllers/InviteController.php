<?php

namespace App\Http\Controllers;

use App\ConstantValues;
use App\Helpers\AuthHelper;
use App\Models\Account;
use App\Models\Code;
use App\Models\Invite;
use App\Models\SafeSoul;
use App\Models\Week;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class InviteController extends Controller
{

    public function activateCode($account)
    {

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        if($account->codes()->where('active', true)->exists()){
           $code =$account->codes()->where('active', true)->latest()->first();


        }else{
            $code = new Code([
                'value' => strtoupper(Str::random(5)),
                'active' => true
            ]);
            $account->codes()->save($code);
        }

        return $code->value;

    }

//    public function inviteUser(Request $request)
//    {
//        // Чий код, дані приглашающего twitter,
//        $account = AuthHelper::auth($request);
//
//        $code = $request->code;
//
//        if(!$account){
//            return response()->json(['message'=>'non authorized'], 401);
//        }
//
//        Week::getCurrentWeekForAccount($account);
//        if ($account->blocked_until && $account->blocked_until > now()) {
//
//            return response()->json(['message' => 'User is blocked for 24 hours'], 429);
//        }
//
//        $maxAttempts = 3;
//        $blockPeriod = now()->addHours(24);
//        $checkCode = Code::where('value', strtoupper($code))->first();
//
//        if(!$checkCode ){
//
//            $account->increment('code_attempts');
//
//
//            if ($account->code_attempts >= $maxAttempts) {
//
//                $account->update(['blocked_until' => $blockPeriod]);
//                return response()->json(['message' => 'User is blocked for 24 hours'], 429);
//            }
//
//
//            return response()->json(['message' => 'Wrong code'], 422);
//        }
//
//        $account->update(['code_attempts' => 0, 'blocked_until'=>null]);
//
//
//        $inviter = Account::where('id', $checkCode->account_id)->first();
//
//        if($checkCode->account_id == $account->id){
//            return response()->json(['message'=>'Self-invite not permitted'], 400);
//        }
//
//        $currentWeek = Week::getCurrentWeekForAccount($account);
//
//        $inviterCurrentWeek = Week::getCurrentWeekForAccount($inviter);
//
//
//        $inviteCheck = DB::table('invites')
//
//            ->where('whom_invited', $account->id)
//            ->pluck('id')
//            ->toArray();
//
//        if(empty($inviteCheck)){
//
//            if ($account->wallet !== '' && isset($account->wallet) && !is_null($account->wallet)) {
//                $process = new Process([
//                    'node',
//                    base_path('node/getInvitedBalanceWallet.js'),
//                    $account->wallet,
//                ]);
//
//                $process->run();
//                if ($process->isSuccessful()) {
//                    $output = Str::replace("\n", "", $process->getOutput());
//
//                    $result = json_decode($output, false, 512, JSON_THROW_ON_ERROR);
//
//                    if (!$result || $result->state != 'success') {
//                        Log::info('cant check wallet balance for user: '. $account->wallet);
//
//                    }else{
//
//                            $wallet_balance = number_format($result->data, 2);
//
//                            $points = null;
//
//                                if($wallet_balance >= 2000 && $wallet_balance < 10000){
//                                    //new user
//
//                                    $points = ConstantValues::balance_2k;
//                                    $account->total_points += $points;
//                                    $currentWeek->invite_points += $points;
//                                    $currentWeek->total_points +=$points;
//                                    $currentWeek->save();
//                                    $account->save();
//                                    //inviter
//
//                                    $safeSoulI = new SafeSoul([
//                                        'account_id' => $inviter->id,
////                                'week_id' => $currentWeek->id,
//                                        'claim_points' => ConstantValues::balance_2k,
//                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
//                                        'query_param' => $checkCode->value
//                                    ]);
//
//                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
//                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::balance_2k);
//
//
//                                }elseif ($wallet_balance >= 10000 && $wallet_balance < 50000){
//                                    $points = ConstantValues::balance_10k;
//                                    $account->total_points += $points;
//                                    $currentWeek->invite_points += $points;
//                                    $currentWeek->total_points +=$points;
//                                    $currentWeek->save();
//                                    $account->save();
//
//                                    //inviter
//
//                                    $safeSoulI = new SafeSoul([
//                                        'account_id' => $inviter->id,
////                                'week_id' => $currentWeek->id,
//                                        'claim_points' => ConstantValues::balance_10k,
//                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
//                                        'query_param' => $checkCode->value
//                                    ]);
//
//                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
//                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::balance_10k);
//
//                                }elseif($wallet_balance >= 50000){
//                                    $points = ConstantValues::balance_50k;
//                                    $account->total_points += $points;
//                                    $currentWeek->invite_points += $points;
//                                    $currentWeek->save();
//                                    $account->save();
//
//                                    //inviter
//
//                                    $safeSoulI = new SafeSoul([
//                                        'account_id' => $inviter->id,
////                                'week_id' => $currentWeek->id,
//                                        'claim_points' => $points,
//                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
//                                        'query_param' => $checkCode->value
//                                    ]);
//
//                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
//                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::balance_10k);
//
//                                }else{
//                                    $points = ConstantValues::null_balance;
//                                    $account->total_points += $points;
//                                    $currentWeek->invite_points += $points;
//                                    $currentWeek->save();
//                                    $account->save();
//
//                                    //inviter
//
//                                    $safeSoulI = new SafeSoul([
//                                        'account_id' => $inviter->id,
////                                'week_id' => $currentWeek->id,
//                                        'claim_points' => ConstantValues::null_balance,
//                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
//                                        'query_param' => $checkCode->value
//                                    ]);
//
//                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
//                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::null_balance);
//                                }
//                    }
//                }
//            }else{
//
//                     $points = ConstantValues::null_balance;
//                    $account->total_points += ConstantValues::null_balance;
//                    $currentWeek->invite_points += $points;
//                    $currentWeek->save();
//                    $account->save();
//
//                    //inviter
//
//                    $safeSoulI = new SafeSoul([
//                        'account_id' => $inviter->id,
////                                'week_id' => $currentWeek->id,
//                        'claim_points' => ConstantValues::null_balance,
//                        'comment' => 'Инвайт человека, рефералка без истории кошелька',
//                        'query_param' => $checkCode->value
//                    ]);
//
//                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
//                    $inviterCurrentWeek->increment('claim_points', ConstantValues::null_balance);
////                }
//
//
//            }
//            $invite = Invite::create([
//                'invited_by'=>$checkCode->account->id,
//                'inviter_wallet'=>$checkCode->account->wallet ?? 'empty',
//                'whom_invited'=>$account->id,
//                'invitee_wallet'=>$account->wallet ?? 'empty',
//                'code_id'=>$checkCode->id,
//                'used_code'=>$checkCode->value
//            ]);
//            return response()->json([
//
//               'points'=> $points
//            ]);
//
//        }else{
//            return response()->json(['message'=>'Already invited'], 400);
//        }
//
//    }


    public function inviteUser(Request $request)
    {
        // Чий код, дані приглашающего twitter,
        $account = AuthHelper::auth($request);

        $code = $request->code;

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        Week::getCurrentWeekForAccount($account);
        if ($account->blocked_until && $account->blocked_until > now()) {

            return response()->json(['message' => 'User is blocked for 24 hours'], 429);
        }

        $maxAttempts = 3;
        $blockPeriod = now()->addHours(24);
        $checkCode = Code::where('value', strtoupper($code))->first();

        if(!$checkCode ){

            $account->increment('code_attempts');


            if ($account->code_attempts >= $maxAttempts) {

                $account->update(['blocked_until' => $blockPeriod]);
                return response()->json(['message' => 'User is blocked for 24 hours'], 429);
            }


            return response()->json(['message' => 'Wrong code'], 422);
        }

        $account->update(['code_attempts' => 0, 'blocked_until'=>null]);


        $inviter = Account::where('id', $checkCode->account_id)->first();

        if($checkCode->account_id == $account->id){
            return response()->json(['message'=>'Self-invite not permitted'], 400);
        }

        $inviteCheck = DB::table('invites')

            ->where('whom_invited', $account->id)
            ->pluck('id')
            ->toArray();

        if(empty($inviteCheck)){
            $invite = Invite::create([
                'invited_by'=>$checkCode->account->id,
                'inviter_wallet'=>$checkCode->account->wallet ?? 'empty',
                'whom_invited'=>$account->id,
                'invitee_wallet'=>$account->wallet ?? 'empty',
                'code_id'=>$checkCode->id,
                'used_code'=>$checkCode->value
            ]);

            return response()->json([
                    'invited_by'=>$checkCode->account->id,
                    'twitter_id'=>$checkCode->account->twitter_id,
                    'twitter_avatar'=>$checkCode->account->twitter_avatar,
                    'twitter_name'=>$checkCode->account->twitter_name,
                    'twitter_username'=>$checkCode->account->twitter_username,
                ]
            );
        }else{
            return response()->json(['message'=>'Already invited'], 423);
        }
    }

}
