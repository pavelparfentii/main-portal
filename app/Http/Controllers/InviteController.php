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

    public function activateCode(Request $request)
    {
        $account = AuthHelper::auth($request);

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        if($account->codes()->where('active', true)->exists()){
           $code =$account->codes()->where('active', true)->latest()->first();

        }else{
            $code = new Code([
                'value' => Str::random(6),
                'active' => true
            ]);
            $account->codes()->save($code);
        }
        return response()->json([
            'code'=>$code->value
        ]);

    }

    public function inviteUser(Request $request)
    {
        $account = AuthHelper::auth($request);

        $code = $request->code;

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }
        if(empty($code) || $code == ''){
            return response()->json(['message'=>'no code provided'], 403);
        }

        $checkCode = Code::where('value', $code)->first();
        $inviter = Account::where('id', $checkCode->account_id)->first();

        if(!$checkCode || !$inviter){
            return response()->json(['message'=>'no such code in database '], 403);
        }

        $currentWeek = Week::getCurrentWeekForAccount($account);


        $inviterCurrentWeek = Week::getCurrentWeekForAccount($inviter);


        $inviteCheck = DB::table('invites')
            ->where('used_code', $code)

            ->where('whom_invited', $account->id)
            ->pluck('id')
            ->toArray();

        if(empty($inviteCheck)){

            if ($account->wallet !== '' && isset($account->wallet) && !is_null($account->wallet)) {
                $process = new Process([
                    'node',
                    base_path('node/getInvitedBalanceWallet.js'),
                    $account->wallet,
                ]);

                $process->run();
                if ($process->isSuccessful()) {
                    $output = Str::replace("\n", "", $process->getOutput());

                    $result = json_decode($output, false, 512, JSON_THROW_ON_ERROR);

//                    dd($result->data);

                    if (!$result || $result->state != 'success') {
                        Log::info('cant check wallet balance for user: '. $account->wallet);

                    }else{

                            $wallet_balance = number_format($result->data, 2);

                                if($wallet_balance >= 2000 && $wallet_balance < 10000){
                                    //new user

//                                    $safeSoul = new SafeSoul([
//                                        'account_id' => $account->id,
////                                'week_id' => $currentWeek->id,
//                                        'points' => ConstantValues::balance_2k,
//                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
//                                        'query_param' => $checkCode->value
//                                    ]);
//
//                                    $currentWeek->safeSouls()->save($safeSoul);
//                                    $currentWeek->increment('points', ConstantValues::balance_2k);
                                    $account->total_points += ConstantValues::balance_2k;
                                    $account->save();
                                    //inviter

                                    $safeSoulI = new SafeSoul([
                                        'account_id' => $inviter->id,
//                                'week_id' => $currentWeek->id,
                                        'claim_points' => ConstantValues::balance_2k,
                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
                                        'query_param' => $checkCode->value
                                    ]);

                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::balance_2k);


                                }elseif ($wallet_balance >= 10000 && $wallet_balance < 50000){

                                    $account->total_points += ConstantValues::balance_10k;
                                    $account->save();

                                    //inviter

                                    $safeSoulI = new SafeSoul([
                                        'account_id' => $inviter->id,
//                                'week_id' => $currentWeek->id,
                                        'claim_points' => ConstantValues::balance_10k,
                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
                                        'query_param' => $checkCode->value
                                    ]);

                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::balance_10k);

                                }elseif($wallet_balance >= 50000){

                                    $account->total_points += ConstantValues::balance_50k;
                                    $account->save();

                                    //inviter

                                    $safeSoulI = new SafeSoul([
                                        'account_id' => $inviter->id,
//                                'week_id' => $currentWeek->id,
                                        'claim_points' => ConstantValues::balance_10k,
                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
                                        'query_param' => $checkCode->value
                                    ]);

                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::balance_10k);

                                }else{

                                    $account->total_points += ConstantValues::null_balance;
                                    $account->save();

                                    //inviter

                                    $safeSoulI = new SafeSoul([
                                        'account_id' => $inviter->id,
//                                'week_id' => $currentWeek->id,
                                        'claim_points' => ConstantValues::null_balance,
                                        'comment' => 'Инвайт человека, рефералка wallet = '. $account->wallet . 'баланс= ' . $wallet_balance,
                                        'query_param' => $checkCode->value
                                    ]);

                                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
                                    $inviterCurrentWeek->increment('claim_points', ConstantValues::null_balance);
                                }

                    }
                }
            }else{

//                $safeSoul =  SafeSoul::where('query_param', $checkCode->value)
//                    ->where('account_id', $account->id)->first();
//                if(!$safeSoul){
//                    $safeSoul = new SafeSoul([
//                        'account_id' => $account->id,
//
//                        'points' => ConstantValues::null_balance,
//                        'comment' => 'Инвайт человека без кошелька, рефералка '. $account->id,
//                        'query_param' => $checkCode->value
//                    ]);

//                    $currentWeek->safeSouls()->save($safeSoul);
//                    $currentWeek->increment('points', ConstantValues::null_balance);
                    $account->total_points += ConstantValues::null_balance;
                    $account->save();

                    //inviter

                    $safeSoulI = new SafeSoul([
                        'account_id' => $inviter->id,
//                                'week_id' => $currentWeek->id,
                        'claim_points' => ConstantValues::null_balance,
                        'comment' => 'Инвайт человека, рефералка без истории кошелька',
                        'query_param' => $checkCode->value
                    ]);

                    $inviterCurrentWeek->safeSouls()->save($safeSoulI);
                    $inviterCurrentWeek->increment('claim_points', ConstantValues::null_balance);
//                }


            }
            $invite = Invite::create([
                'invited_by'=>$checkCode->account->id,
                'inviter_wallet'=>$checkCode->account->wallet ?? 'empty',
                'whom_invited'=>$account->id,
                'invitee_wallet'=>$account->wallet ?? 'empty',
                'code_id'=>$checkCode->id,
                'used_code'=>$checkCode->value
            ]);
            return response()->json([
//               'invited' => DB::table('invites')
//                   ->where('used_code', $checkCode->value)->count(),
               'points'=> $invite
            ]);

        }else{
            return response()->json(['message'=>'Already invited'], 403);
        }

    }

}
