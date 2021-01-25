<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public $count = 5;

    public function decrement()
    {
        if ($this->count) {
            $this->count--;
        }
    }

    public function increment()
    {
        if ($this->count < 100) {
            $this->count++;
        }
    }

    public function render()
    {
        return view('livewire.counter');
    }
}
