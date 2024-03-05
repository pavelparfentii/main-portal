<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Code;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
                'value' => Str::random(36),
                'active' => true
            ]);
            $account->codes()->save($code);
        }
        return response()->json([
            'code'=>$code->value
        ]);

    }
}
