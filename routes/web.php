<?php

use Illuminate\Support\Facades\Route;
use App\Services\MetricsService;
use Illuminate\Support\Facades\Log;

Route::get('/metrics', function (MetricsService $metrics) {
    return response($metrics->render(), 200)
        ->header('Content-Type', \Prometheus\RenderTextFormat::MIME_TYPE);
});

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::view('products', 'products')->name('products');
    Route::view('orders', 'orders')->name('orders');
});

// --- Observability Test Routes ---
Route::prefix('test-observability')->group(function () {
    
    // 1. Slow Request (Latency Test)
    Route::get('/slow', function () {
        $delay = rand(1000, 3000); // 1-3 seconds
        usleep($delay * 1000);
        Log::info("Testing Latency: Slowness created", ['delay_ms' => $delay]);
        return "This request took {$delay}ms. Check 'P95 Latency' in Grafana!";
    });

    // 2. Random Error (Error Rate Test)
    Route::get('/error', function () {
        if (rand(1, 10) > 5) {
            Log::error("Testing Errors: Random system failure triggered!");
            abort(500, "Simulated Critical System Failure!");
        }
        return "Lucky streak! No error this time. Try again.";
    });

    // 3. DB Disaster (N+1 Query / DB Load Test)
    Route::get('/db-load', function () {
        Log::warning("Testing DB: Starting N+1 query simulation");
        // Simulate loading many items inefficiently
        for ($i = 0; $i < 50; $i++) {
            \Illuminate\Support\Facades\DB::select('SELECT 1');
        }
        return "Executed 50 mini-queries. Check 'Database Performance' dashboard!";
    });

    // 4. Large Payload (Saturation Test)
    Route::get('/heavy', function () {
        $size = 2; // MB
        $data = str_repeat("OBSERVABILITY-TEST-", ($size * 1024 * 1024) / 20);
        Log::info("Testing Payload: Sending back large response", ['size_mb' => $size]);
        return response($data)->header('Content-Type', 'text/plain');
    });

    // 5. Auto-Login for Load Testing
    Route::get('/auto-login', function () {
        $user = \App\Models\User::where('email', 'admin@example.com')->first();
        auth()->login($user);
        Log::warning("Load Test: Auto-login used for admin@example.com");
        return response()->json(['status' => 'authenticated']);
    });

    // 6. Stress Test: Order Creation (for k6 and counter seeding)
    Route::post('/stress-order', function (\Illuminate\Http\Request $request) {
        $order = \App\Models\Order::create([
            'order_number' => 'STRESS-' . uniqid(),
            'total_amount' => rand(10, 500),
            'status' => 'completed',
        ]);
        Log::info("Stress Test: Created Order", ['id' => $order->id, 'total' => $order->total_amount]);
        return response()->json(['status' => 'success', 'order_id' => $order->id]);
    });

    // 7. Seed orders/products to populate Prometheus counters
    Route::get('/seed-counters', function () {
        $created = 0;
        for ($i = 0; $i < 5; $i++) {
            \App\Models\Order::create([
                'order_number' => 'SEED-' . uniqid(),
                'total_amount' => rand(10, 500),
                'status'       => 'completed',
            ]);
            $created++;
        }
        Log::info("Counter Seed: Created {$created} orders for Prometheus counter population");
        return response()->json(['status' => 'ok', 'orders_created' => $created]);
    });

    // 8. Trigger a DB error for the DB Error Count panel
    Route::get('/db-error', function () {
        try {
            \Illuminate\Support\Facades\DB::statement('SELECT * FROM non_existent_table_xyz');
        } catch (\Throwable $e) {
            Log::error('DB QueryException: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
            ]);
            return response()->json(['status' => 'db_error_triggered', 'message' => $e->getMessage()]);
        }
        return response()->json(['status' => 'no_error']);
    });
});

require __DIR__.'/auth.php';
