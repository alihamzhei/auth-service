<?php

namespace App\Infrastructure\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitLogin
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->ip();
        
        if ($this->limiter->tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => 'Too many login attempts. Please try again later.'
            ], 429);
        }
        
        $this->limiter->hit($key, 60); // 1 minute
        
        $response = $next($request);
        
        // If login was successful, clear the rate limiter
        if ($response->getStatusCode() === 200) {
            $this->limiter->clear($key);
        }
        
        return $response;
    }
}