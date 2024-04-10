<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $apiKey = $request->header('Authorization');

        if (!$apiKey) {
            return response()->json(['Unauthorized'], 401);
        }

        $token = $request->header('Authorization');
        if($token !== 'Bearer ux9AgzrHauOq6sF8kZSetP4eCAB7z2OoxLOQP1fpQCZflMrPBQdUVdQehCdGDrPZ'){
            return \response()->json(['Unauthorized'],401);
        }


        return $next($request);
    }
}
