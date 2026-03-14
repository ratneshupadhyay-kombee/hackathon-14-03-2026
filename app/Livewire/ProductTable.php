<?php

namespace App\Livewire;

use App\Models\Product;
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

final class ProductTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'product-table';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            PowerGrid::exportable('products-export')
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
        return Product::query();
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('price')
            ->add('price_formatted', fn (Product $model) => '$' . number_format((float)$model->price, 2))
            ->add('stock')
            ->add('is_active')
            ->add('is_active_label', fn (Product $model) => $model->is_active
                ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">● Active</span>'
                : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">● Inactive</span>'
            )
            ->add('created_at_formatted', fn (Product $model) => Carbon::parse($model->created_at)->format('d M Y'));
    }

    public function columns(): array
    {
        return [
            Column::make('#', 'id')
                ->sortable(),

            Column::make('Name', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Price', 'price_formatted', 'price')
                ->sortable(),

            Column::make('Stock', 'stock')
                ->sortable(),

            Column::make('Status', 'is_active_label', 'is_active'),

            Column::make('Created', 'created_at_formatted', 'created_at')
                ->sortable(),

            Column::action('Actions'),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::inputText('name')->operators(['contains']),
            Filter::boolean('is_active')->label('Active', 'Inactive'),
        ];
    }

    #[\Livewire\Attributes\On('delete-product')]
    public function deleteProduct($rowId): void
    {
        $product = Product::find($rowId);
        if ($product) {
            $name = $product->name;
            $product->delete();
            session()->flash('message', "Product '{$name}' deleted successfully.");
        }
        $this->dispatch('pg:eventRefresh-product-table');
    }

    #[\Livewire\Attributes\On('bulk-delete-products')]
    public function bulkDeleteProducts(): void
    {
        if (empty($this->checkboxValues)) {
            return;
        }
        $count = count($this->checkboxValues);
        Product::whereIn('id', $this->checkboxValues)->delete();
        $this->checkboxValues = [];
        session()->flash('message', "{$count} product(s) deleted successfully.");
        $this->dispatch('pg:eventRefresh-product-table');
    }

    public function actions(Product $row): array
    {
        return [
            Button::add('delete-product')
                ->slot('Delete')
                ->class('px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium rounded-lg border border-red-200 transition-colors')
                ->dispatch('delete-product', ['rowId' => $row->id]),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('bulk-delete')
                ->slot('Bulk Delete')
                ->class('px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-colors')
                ->dispatch('bulk-delete-products', []),
        ];
    }
}
