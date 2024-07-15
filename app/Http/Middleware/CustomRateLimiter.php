<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $identifier = $this->resolveRequestIdentifier($request);

        if (RateLimiter::tooManyAttempts($identifier, 60)) {
            $retryAfter = RateLimiter::availableIn($identifier);

            return response()->json([
                'message' => 'Too many requests',
                'retry_after' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($identifier, 60);

        return $next($request);
    }

    protected function resolveRequestIdentifier(Request $request)
    {
        $token = $request->bearerToken();
        if ($token) {
            try {
                $payload = JWTAuth::setToken($token)->getPayload();
                return $payload->get('sub');
            } catch (\Exception $e) {
                Log::info('resolve error ' . $e);
            }
        }

        return $request->ip();
    }
}
