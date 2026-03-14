<?php

namespace App\Livewire;

use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
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

    // Edit modal state
    public ?int $editingProductId = null;
    public string $editName       = '';
    public string $editPrice      = '';
    public int    $editStock      = 0;
    public bool   $editIsActive   = true;

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
            Column::make('#', 'id')->sortable(),
            Column::make('Name', 'name')->sortable()->searchable(),
            Column::make('Price', 'price_formatted', 'price')->sortable(),
            Column::make('Stock', 'stock')->sortable(),
            Column::make('Status', 'is_active_label', 'is_active'),
            Column::make('Created', 'created_at_formatted', 'created_at')->sortable(),
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

    // ── Edit ──────────────────────────────────────────────────────────────────

    #[\Livewire\Attributes\On('edit-product')]
    public function openEdit(int $rowId): void
    {
        $product = Product::findOrFail($rowId);
        $this->editingProductId = $product->id;
        $this->editName         = $product->name;
        $this->editPrice        = (string) $product->price;
        $this->editStock        = $product->stock;
        $this->editIsActive     = (bool) $product->is_active;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editName'  => 'required|string|min:2|max:255',
            'editPrice' => 'required|numeric|min:0',
            'editStock' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($this->editingProductId);
        $product->update([
            'name'      => $this->editName,
            'price'     => $this->editPrice,
            'stock'     => $this->editStock,
            'is_active' => $this->editIsActive,
        ]);

        Log::info('Product updated', ['product_id' => $product->id, 'name' => $product->name]);

        $this->cancelEdit();
        session()->flash('message', "Product '{$product->name}' updated successfully.");
        $this->dispatch('pg:eventRefresh-product-table');
    }

    public function cancelEdit(): void
    {
        $this->editingProductId = null;
        $this->editName         = '';
        $this->editPrice        = '';
        $this->editStock        = 0;
        $this->editIsActive     = true;
        $this->resetValidation();
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    #[\Livewire\Attributes\On('delete-product')]
    public function deleteProduct(int $rowId): void
    {
        $product = Product::find($rowId);
        if ($product) {
            $name = $product->name;
            $product->delete();
            Log::info('Product deleted', ['name' => $name]);
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
        Log::info('Bulk products deleted', ['count' => $count]);
        session()->flash('message', "{$count} product(s) deleted successfully.");
        $this->dispatch('pg:eventRefresh-product-table');
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function actions(Product $row): array
    {
        return [
            Button::add('edit-product')
                ->slot('✏️ Edit')
                ->class('px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-medium rounded-lg border border-indigo-200 transition-colors mr-1')
                ->dispatch('edit-product', ['rowId' => $row->id]),

            Button::add('delete-product')
                ->slot('🗑️ Delete')
                ->class('px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium rounded-lg border border-red-200 transition-colors')
                ->dispatch('delete-product', ['rowId' => $row->id]),
        ];
    }

    public function header(): array
    {
        return [
            Button::add('bulk-delete')
                ->slot('🗑️ Bulk Delete')
                ->class('px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-colors')
                ->dispatch('bulk-delete-products', []),
        ];
    }
}
