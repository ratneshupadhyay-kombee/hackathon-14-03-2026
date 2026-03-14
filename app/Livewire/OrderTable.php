<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;

final class OrderTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'order-table';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('orders-export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            PowerGrid::header()
                ->showSearchInput()
                ->showToggleColumns(),
            PowerGrid::footer()
                ->showPerPage(10)
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Order::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        $statusConfig = [
            'pending'    => ['bg-amber-100 text-amber-800 border-amber-200',  '⏳'],
            'processing' => ['bg-blue-100 text-blue-800 border-blue-200',    '🔄'],
            'completed'  => ['bg-green-100 text-green-800 border-green-200', '✅'],
            'cancelled'  => ['bg-red-100 text-red-800 border-red-200',       '❌'],
        ];

        return PowerGrid::fields()
            ->add('id')
            ->add('order_number')
            ->add('total_amount')
            ->add('total_amount_formatted', fn (Order $model) => '$' . number_format((float)$model->total_amount, 2))
            ->add('status')
            ->add('status_badge', function (Order $model) use ($statusConfig) {
                [$classes, $icon] = $statusConfig[$model->status] ?? ['bg-gray-100 text-gray-800 border-gray-200', '•'];
                $label = ucfirst($model->status);
                return "<span class=\"inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border {$classes}\">{$icon} {$label}</span>";
            })
            ->add('created_at_formatted', fn (Order $model) => Carbon::parse($model->created_at)->format('d M Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')
                ->sortable(),

            Column::make('Order #', 'order_number')
                ->sortable()
                ->searchable(),

            Column::make('Amount', 'total_amount_formatted', 'total_amount')
                ->sortable(),

            Column::make('Status', 'status_badge', 'status'),

            Column::make('Created', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Actions'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('order_number')->operators(['contains']),
            Filter::select('status', 'status')
                ->dataSource([
                    ['status' => 'pending',    'label' => '⏳ Pending'],
                    ['status' => 'processing', 'label' => '🔄 Processing'],
                    ['status' => 'completed',  'label' => '✅ Completed'],
                    ['status' => 'cancelled',  'label' => '❌ Cancelled'],
                ])
                ->optionValue('status')
                ->optionLabel('label'),
        ];
    }

    #[\Livewire\Attributes\On('update-order-status')]
    public function updateOrderStatus($rowId, $status): void
    {
        $tracer = app(\OpenTelemetry\API\Trace\TracerInterface::class);
        $span = $tracer->spanBuilder('Livewire Action: updateOrderStatus')
            ->setAttribute('order.rowId', $rowId)
            ->setAttribute('order.status', $status)
            ->startSpan();
            
        $scope = $span->activate();

        try {
            $processor = app(\App\Services\OrderProcessingService::class);
            $processor->processStatusUpdate($rowId, $status);

            $order = Order::find($rowId);
            if ($order) {
                $order->update(['status' => $status]);
                $this->dispatch('pg:eventRefresh-order-table');
            }
        } finally {
            $span->end();
            $scope->detach();
        }
    }

    #[\Livewire\Attributes\On('delete-order')]
    public function deleteOrder($rowId): void
    {
        $order = Order::find($rowId);
        if ($order) {
            $num = $order->order_number;
            $order->delete();
            session()->flash('message', "Order #{$num} deleted successfully.");
        }
        $this->dispatch('pg:eventRefresh-order-table');
    }

    public function actions(Order $row): array
    {
        return [
            Button::add('mark-complete')
                ->slot('✅ Complete')
                ->class('px-2.5 py-1.5 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-lg border border-green-200 transition-colors mr-1')
                ->dispatch('update-order-status', ['rowId' => $row->id, 'status' => 'completed']),

            Button::add('delete-order')
                ->slot('Delete')
                ->class('px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium rounded-lg border border-red-200 transition-colors')
                ->dispatch('delete-order', ['rowId' => $row->id]),
        ];
    }
}
