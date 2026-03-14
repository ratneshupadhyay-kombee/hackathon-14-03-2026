<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Validate;
use Livewire\Component;

class OrderForm extends Component
{
    #[Validate('required|min:3|unique:orders,order_number')]
    public $order_number = '';

    #[Validate('required|numeric|min:0')]
    public $total_amount = '';

    public $product_id = '';
    public $status = 'pending';

    public function updatedProductId($value)
    {
        if ($value) {
            $product = \App\Models\Product::find($value);
            if ($product) {
                $this->total_amount = $product->price;
            }
        }
    }

    public function save()
    {
        $this->validate();

        Order::create([
            'order_number' => $this->order_number,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
        ]);

        $this->reset(['order_number', 'total_amount', 'product_id']);
        $this->status = 'pending';
        
        $this->dispatch('pg:eventRefresh-order-table'); 
        session()->flash('status', 'Order successfully created.');
    }

    public function render()
    {
        return view('livewire.order-form');
    }
}
