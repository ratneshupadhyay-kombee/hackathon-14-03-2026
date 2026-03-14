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
    }
}
