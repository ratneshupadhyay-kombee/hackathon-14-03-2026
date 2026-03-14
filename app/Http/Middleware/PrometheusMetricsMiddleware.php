<?php

namespace App\Http\Middleware;

use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;

class PrometheusMetricsMiddleware
{
    public function __construct(private MetricsService $metrics) {}

    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $startTime;

        try {
            $registry = $this->metrics->getRegistry();
            if (!$registry) {
                return $response;
            }

            $method = $request->method();
            $path = $request->route() ? $request->route()->uri() : $request->path();
            $status = $response->status();

            // Total Requests
            $registry->getOrRegisterCounter(
                'laravel', 'http_requests_total', 'Total Requests', ['method', 'endpoint', 'status']
            )->inc([$method, $path, $status]);

            // Response Time
            $registry->getOrRegisterHistogram(
                'laravel', 'http_response_time_seconds', 'Response Time', ['method', 'endpoint']
            )->observe($duration, [$method, $path]);

            // Errors
            if ($status >= 400) {
                $registry->getOrRegisterCounter(
                    'laravel', 'http_errors_total', 'Total Errors', ['method', 'endpoint', 'status']
                )->inc([$method, $path, $status]);
            }
        } catch (\Throwable $e) {
            // Never let metrics crash the app
        }

        return $response;
    }
}
