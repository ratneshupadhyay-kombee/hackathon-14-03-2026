<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OpenTelemetryMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Guard: if OTel classes aren't loaded, skip tracing entirely
        if (!class_exists(\OpenTelemetry\API\Trace\TracerInterface::class)) {
            return $next($request);
        }

        try {
            /** @var \OpenTelemetry\API\Trace\TracerInterface $tracer */
            $tracer = app(\OpenTelemetry\API\Trace\TracerInterface::class);

            $span = $tracer->spanBuilder('HTTP ' . $request->method())
                ->setAttribute('http.method', $request->method())
                ->setAttribute('http.url', $request->fullUrl())
                ->setAttribute('http.route', $request->route() ? $request->route()->uri() : $request->path())
                ->startSpan();

            $scope = $span->activate();

            // Share trace_id with Loki logs for correlation
            Log::shareContext(['trace_id' => $span->getContext()->getTraceId()]);

        } catch (\Throwable $e) {
            // OTel failed to initialize — skip tracing, never block the request
            return $next($request);
        }

        try {
            $response = $next($request);

            $span->setAttribute('http.status_code', $response->status());

            if ($response->status() >= 500) {
                $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, 'Server Error');
            }

            return $response;
        } catch (\Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $exception->getMessage());
            throw $exception;
        } finally {
            $span->end();
            $scope->detach();
        }
    }
}
