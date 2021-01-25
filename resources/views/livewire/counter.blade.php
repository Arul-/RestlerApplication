<div>
    {{-- Be like water. --}}
    Counter <span>{{ $count }}</span>
    @if($count)
        <button wire:click="decrement">-</button>
    @endif
    <button wire:click="increment">+</button>
</div>
