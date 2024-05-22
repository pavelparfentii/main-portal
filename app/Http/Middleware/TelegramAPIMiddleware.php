<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TelegramAPIMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('Authorization');

        $checkCache = Cache::get($apiKey);
//        dd($checkCache);
//        dd($apiKey);

        if (!$apiKey) {
            return response()->json(['Unauthorized'], 401);
        }elseif (!$checkCache){
            return response()->json(['message' => 'apikey expired'], 423);
        }


        $token = $request->header('Authorization');


        if($token !== $checkCache){
            return \response()->json(['Unauthorized'],401);
        }

        return $next($request);
    }
}
