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

        $inviteCheck = Invite::where('whom_invited', $account->id)
            ->first();

        if(!$inviteCheck){
            $invite = Invite::create([
                'invited_by'=>$checkCode->account->id,
                'inviter_wallet'=>$checkCode->account->wallet ?? 'empty',
                'whom_invited'=>$account->id,
                'invitee_wallet'=>$account->wallet ?? 'empty',
                'code_id'=>$checkCode->id,
                'used_code'=>$checkCode->value
            ]);



            return response()->json([
                    'id'=>$checkCode->account->id,
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

    public function activateCodeTelegram($account)
    {

        if(!$account){
            return response()->json(['message'=>'non authorized'], 401);
        }

        if($account->codes()->where('active', true)->exists()){
            $code =$account->codes()->where('active', true)->latest()->first();


        }else{
            $code = Code::on('pgsql_telegrams')->create([
                'value' => strtoupper(Str::random(5)),
                'active' => true,
                'account_id'=>$account->id
            ]);

        }

        return $code->value;

    }

}
