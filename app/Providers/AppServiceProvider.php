<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\MetricsService::class, function () {
            return new \App\Services\MetricsService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            function () {
                $metrics = app(\App\Services\MetricsService::class);
                $counter = $metrics->getRegistry()->getOrRegisterCounter(
                    'laravel',
                    'login_failed_total',
                    'Total number of failed login attempts'
                );
                $counter->inc();
            }
        );

        \App\Models\Order::created(function () {
            $metrics = app(\App\Services\MetricsService::class);
            $counter = $metrics->getRegistry()->getOrRegisterCounter(
                'laravel',
                'orders_created_total',
                'Total number of generated orders'
            );
            $counter->inc();
        });

        \App\Models\Product::created(function () {
            try {
                $metrics = app(\App\Services\MetricsService::class);
                $metrics->getRegistry()->getOrRegisterCounter(
                    'laravel', 'products_created_total', 'Total products created'
                )->inc();
            } catch (\Throwable) {}
        });

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Validation\ValidationException::class,
            function (\Illuminate\Validation\ValidationException $e) {
                try {
                    $metrics = app(\App\Services\MetricsService::class);
                    $counter = $metrics->getRegistry()->getOrRegisterCounter(
                        'laravel',
                        'validation_errors_total',
                        'Total number of validation errors'
                    );
                    $counter->inc();
                    \Illuminate\Support\Facades\Log::warning('Validation error', [
                        'errors' => $e->errors(),
                    ]);
                } catch (\Throwable) {}
            }
        );
    }
}
