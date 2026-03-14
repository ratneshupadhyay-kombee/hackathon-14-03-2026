<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">📦 Order Management</h2>
                <p class="text-sm text-gray-500 mt-1">Track and manage customer orders</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if (session('message'))
                <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-xl shadow-sm" role="alert">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span class="font-medium">{{ session('message') }}</span>
                </div>
            @endif

            {{-- Order Stats --}}
            @php
                $stats = [
                    ['label' => 'Total Orders', 'value' => \App\Models\Order::count(), 'color' => 'indigo'],
                    ['label' => 'Pending', 'value' => \App\Models\Order::where('status', 'pending')->count(), 'color' => 'yellow'],
                    ['label' => 'Processing', 'value' => \App\Models\Order::where('status', 'processing')->count(), 'color' => 'blue'],
                    ['label' => 'Completed', 'value' => \App\Models\Order::where('status', 'completed')->count(), 'color' => 'green'],
                ];
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($stats as $stat)
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Create Order Form --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800">Create New Order</h3>
                </div>
                <div class="p-6">
                    <livewire:order-form />
                </div>
            </div>

            {{-- Orders Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-800">All Orders</h3>
                </div>
                <div class="p-4">
                    <livewire:order-table />
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
