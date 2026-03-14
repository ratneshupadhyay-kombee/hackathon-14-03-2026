<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Product;

class SeedMetrics extends Command
{
    protected $signature = 'metrics:seed';
    protected $description = 'Seed Prometheus counters and generate DB query logs for Grafana dashboards';

    public function handle(): void
    {
        // 1. Create orders to increment laravel_orders_created_total
        $this->info('Creating orders...');
        for ($i = 0; $i < 8; $i++) {
            Order::create([
                'order_number' => 'SEED-' . uniqid(),
                'total_amount' => rand(10, 500),
                'status'       => 'completed',
            ]);
        }
        $this->info('8 orders created — laravel_orders_created_total incremented');

        // 2. Run DB queries so DB::listen logs them with db_execution_time_ms
        $this->info('Running DB queries for Avg Query Duration panel...');
        for ($i = 0; $i < 20; $i++) {
            DB::select('SELECT * FROM orders LIMIT 10');
            DB::select('SELECT * FROM products LIMIT 10');
        }
        $this->info('40 DB queries executed and logged');

        // 3. Heavier queries to push some over 100ms threshold
        $this->info('Running heavier queries for Slow Queries panel...');
        for ($i = 0; $i < 5; $i++) {
            DB::select('SELECT o.*, p.name FROM orders o CROSS JOIN products p LIMIT 500');
        }
        $this->info('5 heavier cross-join queries executed');

        // 4. Trigger a DB error for DB Error Count panel
        $this->info('Triggering DB error for DB Error Count panel...');
        try {
            DB::statement('SELECT * FROM non_existent_table_xyz_abc');
        } catch (\Throwable $e) {
            Log::error('DB QueryException: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
            ]);
            $this->warn('DB error logged: ' . substr($e->getMessage(), 0, 80));
        }

        $this->info('Done! Wait ~30s for Promtail to ship logs, then check Grafana.');
    }
}
