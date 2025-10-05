<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RequestLogger
{
    public function handle($request, Closure $next)
    {

        $start = microtime(true);

        // Log basic request info
        Log::channel('api')->info('Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'time' => Carbon::now()->toISOString(),
        ]);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        // Log basic response info
        Log::channel('api')->info('Response', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'time' => Carbon::now()->toISOString(),
        ]);

        return $response;
    }
}