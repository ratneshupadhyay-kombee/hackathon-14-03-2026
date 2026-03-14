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
                try {
                    app(\App\Services\MetricsService::class)->increment('laravel_login_failed_total');
                } catch (\Throwable) {}
            }
        );

        \App\Models\Order::created(function () {
            try {
                app(\App\Services\MetricsService::class)->increment('laravel_orders_created_total');
            } catch (\Throwable) {}
        });

        \App\Models\Product::created(function () {
            try {
                app(\App\Services\MetricsService::class)->increment('laravel_products_created_total');
            } catch (\Throwable) {}
        });

        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Validation\ValidationException::class,
            function (\Illuminate\Validation\ValidationException $e) {
                try {
                    app(\App\Services\MetricsService::class)->increment('laravel_validation_errors_total');
                    \Illuminate\Support\Facades\Log::warning('Validation error', ['errors' => $e->errors()]);
                } catch (\Throwable) {}
            }
        );
    }
}
