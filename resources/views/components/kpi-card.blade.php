@props(['label', 'value', 'sub' => '', 'icon' => 'cash', 'color' => '#0b3d91', 'bg' => '#e8eefc','ismoney'=>false])

<div class="kpi">
    <div class="kpi-top">
        <span class="kpi-ico" style="background: {{ $bg }}; color: {{ $color }};">
            @switch($icon)
                @case('cash')
                    <svg viewBox="0 0 24 24" class="w-4 h-4"><rect x="2" y="6" width="20" height="12" rx="2"/><circle
                            cx="12" cy="12" r="2.5"/></svg>
                    @break
                @case('check')
                    <svg viewBox="0 0 24 24" class="w-4 h-4"><path d="M20 6L9 17l-5-5"/></svg>
                    @break
                @case('clock')
                    <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                            d="M12 7v5l3 2"/></svg>
                    @break
                @case('alert')
                    <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                            d="M12 8v5M12 16.5v.5"/></svg>
                    @break
                @case('cal')
                    <svg viewBox="0 0 24 24" class="w-4 h-4"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path
                            d="M16 2v4M8 2v4M3 10h18"></path></svg>
                    @break
                @case('chat')
                    <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                            d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path></svg>
                    @break
                @case('list')
                    <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                            d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                    @break
            @endswitch
        </span>
        <span class="kpi-label">{{ $label }}</span>
    </div>
    <div class="kpi-value text-2xl font-bold">
        @if($ismoney)
            <x-money :value="$value" :short="true"/>
        @else
            {{ $value }}
        @endif
    </div>
    @if($sub)
        <div class="kpi-foot">{{ $sub }}</div>
    @endif
</div>
