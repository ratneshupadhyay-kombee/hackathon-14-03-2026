<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $traceId = $request->header('x-trace-id', Str::uuid()->toString());

        // shareContext injects these fields into every log entry for this request
        Log::shareContext([
            'trace_id' => $traceId,
            'user_id'  => auth()->check() ? auth()->id() : 'guest',
            'endpoint' => $request->method() . ' ' . $request->path(),
        ]);

        return $next($request);
    }
}
