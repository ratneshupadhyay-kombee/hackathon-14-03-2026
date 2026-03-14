<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">📊 Dashboard</h2>
                <p class="text-sm text-gray-500 mt-1">Welcome back, {{ auth()->user()->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="http://localhost:3000" target="_blank"
                    class="flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-lg transition">
                    📈 Grafana
                </a>
                <a href="http://localhost:9090" target="_blank"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg transition">
                    🔥 Prometheus
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $totalOrders    = \App\Models\Order::count();
                $totalProducts  = \App\Models\Product::count();
                $pendingOrders  = \App\Models\Order::where('status', 'pending')->count();
                $completedOrders = \App\Models\Order::where('status', 'completed')->count();
                $totalRevenue   = \App\Models\Order::where('status', 'completed')->sum('total_amount');
                $activeProducts = \App\Models\Product::where('is_active', true)->count();
            @endphp

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Orders</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalOrders }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1">{{ $pendingOrders }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Completed</p>
                    <p class="text-3xl font-bold text-green-600 mt-1">{{ $completedOrders }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Revenue</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">${{ number_format($totalRevenue, 0) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Products</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalProducts }}</p>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Active Products</p>
                    <p class="text-3xl font-bold text-green-600 mt-1">{{ $activeProducts }}</p>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('orders') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-2xl group-hover:bg-indigo-200 transition">📦</div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Manage Orders</h3>
                            <p class="text-sm text-gray-500">Create, edit, filter and track orders</p>
                        </div>
                    </div>
                </a>
                <a href="{{ route('products') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition group">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center text-2xl group-hover:bg-violet-200 transition">🛒</div>
                        <div>
                            <h3 class="font-semibold text-gray-800">Manage Products</h3>
                            <p class="text-sm text-gray-500">Add, edit, and manage your inventory</p>
                        </div>
                    </div>
                </a>
            </div>

            {{-- Observability Test Panel --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-4">🔬 Observability Test Suite</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <a href="/test-observability/slow" target="_blank"
                        class="flex flex-col items-center gap-2 p-4 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition text-center">
                        <span class="text-2xl">⚡</span>
                        <span class="text-xs font-semibold text-amber-800">Slow Request</span>
                        <span class="text-xs text-amber-600">1–3s delay</span>
                    </a>
                    <a href="/test-observability/error" target="_blank"
                        class="flex flex-col items-center gap-2 p-4 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition text-center">
                        <span class="text-2xl">⚠️</span>
                        <span class="text-xs font-semibold text-red-800">Random Error</span>
                        <span class="text-xs text-red-600">50% 500 error</span>
                    </a>
                    <a href="/test-observability/db-load" target="_blank"
                        class="flex flex-col items-center gap-2 p-4 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition text-center">
                        <span class="text-2xl">💾</span>
                        <span class="text-xs font-semibold text-blue-800">DB N+1 Load</span>
                        <span class="text-xs text-blue-600">50 queries</span>
                    </a>
                    <a href="/test-observability/heavy" target="_blank"
                        class="flex flex-col items-center gap-2 p-4 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition text-center">
                        <span class="text-2xl">🐘</span>
                        <span class="text-xs font-semibold text-purple-800">Heavy Payload</span>
                        <span class="text-xs text-purple-600">2MB response</span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
