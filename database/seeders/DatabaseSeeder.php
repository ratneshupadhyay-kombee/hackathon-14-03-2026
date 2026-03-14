<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user if not exists
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );

        // Seed Products
        $products = [
            ['name' => 'Loki Log Collector',    'price' => 29.99,  'stock' => 100, 'is_active' => true],
            ['name' => 'Prometheus Scraper',     'price' => 49.99,  'stock' => 50,  'is_active' => true],
            ['name' => 'Grafana Dashboard Kit',  'price' => 99.99,  'stock' => 20,  'is_active' => true],
            ['name' => 'Tempo Trace Analyzer',   'price' => 79.99,  'stock' => 30,  'is_active' => false],
            ['name' => 'OTEL Sidecar App',       'price' => 19.99,  'stock' => 200, 'is_active' => true],
            ['name' => 'Docker Base Template',   'price' => 9.99,   'stock' => 999, 'is_active' => true],
            ['name' => 'Laravel Starter Kit',    'price' => 149.00, 'stock' => 15,  'is_active' => true],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['name' => $product['name']], $product);
        }

        // Seed Orders
        $orders = [
            ['order_number' => 'ORD-2024-001', 'total_amount' => 129.98, 'status' => 'completed'],
            ['order_number' => 'ORD-2024-002', 'total_amount' => 49.99,  'status' => 'pending'],
            ['order_number' => 'ORD-2024-003', 'total_amount' => 19.99,  'status' => 'processing'],
            ['order_number' => 'ORD-2024-004', 'total_amount' => 249.97, 'status' => 'completed'],
            ['order_number' => 'ORD-2024-005', 'total_amount' => 99.99,  'status' => 'cancelled'],
            ['order_number' => 'ORD-2024-006', 'total_amount' => 79.99,  'status' => 'pending'],
        ];

        foreach ($orders as $order) {
            Order::firstOrCreate(['order_number' => $order['order_number']], $order);
        }
    }
}
