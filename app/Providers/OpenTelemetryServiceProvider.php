<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SemConv\ResourceAttributes;
use OpenTelemetry\API\Trace\NoopTracer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

class OpenTelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TracerInterface::class, function () {
            try {
                // Ensure we have the base classes loaded
                if (!class_exists(TracerProvider::class)) {
                    return \OpenTelemetry\API\Trace\NoopTracer::getInstance();
                }

                $resource = ResourceInfoFactory::emptyResource()->merge(ResourceInfo::create(
                    \OpenTelemetry\SDK\Common\Attribute\Attributes::create([
                        ResourceAttributes::SERVICE_NAME    => 'laravel_app',
                        ResourceAttributes::SERVICE_VERSION => '1.0.0',
                    ])
                ));

                // Use a very short timeout for the transport
                $transport = (new OtlpHttpTransportFactory())->create(
                    'http://hackathon_tempo:4318/v1/traces', 
                    'application/x-protobuf'
                );

                $exporter = new SpanExporter($transport);
                
                // SimpleSpanProcessor is synchronous and safer for debugging hangs than Batch
                $spanProcessor = new SimpleSpanProcessor($exporter);

                $tracerProvider = new TracerProvider($spanProcessor, null, $resource);

                return $tracerProvider->getTracer('laravel.tracer');
            } catch (\Throwable $e) {
                // Return a NoopTracer if OTel setup fails — never hang the app
                return \OpenTelemetry\API\Trace\NoopTracer::getInstance();
            }
        });
    }

    public function boot(): void
    {
        try {
            DB::listen(function (QueryExecuted $query) {
                // Always log DB queries as structured JSON for Loki
                \Illuminate\Support\Facades\Log::debug('DB query executed', [
                    'db_system'            => 'mysql',
                    'db_statement'         => $query->sql,
                    'db_execution_time_ms' => round($query->time, 2),
                    'db_connection'        => $query->connection->getName(),
                ]);

                // Resolve tracer defensively
                if (!app()->bound(TracerInterface::class)) {
                    return;
                }

                $tracer = app(TracerInterface::class);
                if ($tracer instanceof \OpenTelemetry\API\Trace\NoopTracer) {
                    return;
                }

                $span = $tracer->spanBuilder('DB ' . $query->connection->getName())
                    ->setAttribute('db.system', 'mysql')
                    ->setAttribute('db.statement', $query->sql)
                    ->setAttribute('db.execution_time_ms', $query->time)
                    ->startSpan();

                $span->end();
            });
        } catch (\Throwable $e) {
            // Silently fail for OTel
        }
    }
}
