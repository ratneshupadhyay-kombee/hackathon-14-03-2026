<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class LogContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Require trace_id from headers (like OpenTelemetry) or generate one
        $traceId = $request->header('x-trace-id', Str::uuid()->toString());

        Context::add('trace_id', $traceId);
        Context::add('user_id', auth()->check() ? auth()->id() : 'guest');
        Context::add('endpoint', $request->method() . ' ' . $request->path());

        return $next($request);
    }
}
