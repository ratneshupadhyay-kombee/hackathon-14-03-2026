<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ProductForm extends Component
{
    #[Validate('required|string|min:2|max:255')]
    public $name = '';

    #[Validate('required|numeric|min:0')]
    public $price = '';

    #[Validate('required|integer|min:0')]
    public $stock = 0;

    public bool $is_active = true;

    public function save()
    {
        $this->validate();

        Product::create([
            'name'      => $this->name,
            'price'     => $this->price,
            'stock'     => $this->stock,
            'is_active' => $this->is_active,
        ]);

        $this->reset(['name', 'price', 'stock']);
        $this->is_active = true;

        $this->dispatch('pg:eventRefresh-product-table');
        session()->flash('status', "Product '{$this->name}' created successfully.");
    }

    public function render()
    {
        return view('livewire.product-form');
    }
}
