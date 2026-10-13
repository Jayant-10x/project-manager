<div>
    <!-- Page Header Count Summary -->
    <div class="page-head mb-4">
        <span class="info text-sm text-[#8894ac]">
            {{ $logs->total() }} recent entries
        </span>
    </div>

    <!-- Activity Feed Card -->
    <div class="card">
        <div class="card-body p-5">
            @forelse($logs as $a)
                @php
                    $act = strtolower($a->action ?? '');
                    $dotClass = match ($act) {
                        'payment' => 'g',
                        'delete'  => 'r',
                        'create', 'login', 'reset' => 'n',
                        'upload'  => 'v',
                        default   => 'a',
                    };
                    $actor = $a->user?->name ?? ($a->who ?? 'System');
                @endphp
                <div class="tl flex gap-3 py-2.5 border-b last:border-b-0 border-[#eef2f9]">
                    <i class="dot {{ $dotClass }} mt-1 flex-shrink-0"></i>
                    <div style="flex:1;min-width:0">
                        <div class="t text-xs text-[#0d1b34] leading-relaxed">
                            <b class="font-semibold">{{ $actor }}</b> · {{ $a->action }}
                            @if($a->entity_code)
                                <span class="pill">{{ $a->entity_code }}</span>
                            @endif
                            @if($a->detail)
                                <div class="text-xs text-[#4a5875] mt-0.5 leading-normal">{{ $a->detail }}</div>
                            @endif
                        </div>
                        <div class="w text-[11px] text-[#8894ac] mt-1">
                            {{ $a->at_time ? \Carbon\Carbon::parse($a->at_time)->format('d M Y, H:i') : $a->created_at?->format('d M Y, H:i') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty">
                     <span class="flex items-center justify-center">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                     </span>
                    <p>No activity yet</p>
                    <div class="sm">Activity will be recorded here</div>
                </div>
            @endforelse
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $logs->links() }}</div>
    </div>
</div>
