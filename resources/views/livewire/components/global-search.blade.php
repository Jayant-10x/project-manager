<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <div class="gsearch">
        <svg viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7"/>
            <path d="M21 21l-4.3-4.3"/>
        </svg>
        <input wire:model.live.debounce.300ms="query"
               @focus="open = true"
               :placeholder="window.innerWidth <= 640 ? 'Search...' : 'Search (Ctrl + K)...'"
               placeholder="Search..."
               id="gsearch">
    </div>

    @if(!empty($results))
        <div x-show="open" class="sres">
            @foreach($results as $res)
                <a href="{{ $res['url'] }}" wire:navigate class="sres-item block">
                    <div class="tt font-bold">{{ $res['title'] }}</div>
                    <div class="ss text-xs text-gray-500">{{ $res['type'] }} · {{ $res['sub'] }}</div>
                </a>
            @endforeach
        </div>
    @endif
</div>
