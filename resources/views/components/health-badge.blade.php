@props(['health' => 'On Track'])

@php
    $styles = [
        'On Track'  => ['cls' => 'h-track', 'dot' => '#5aa236'],
        'At Risk'   => ['cls' => 'h-risk',  'dot' => '#e0a13a'],
        'Delayed'   => ['cls' => 'h-late',  'dot' => '#d94b4b'],
        'Completed' => ['cls' => 'h-done',  'dot' => '#2e75b6'],
    ][$health] ?? ['cls' => 'h-track', 'dot' => '#5aa236'];
@endphp

<span class="health {{ $styles['cls'] }}">
    <i style="background: {{ $styles['dot'] }}"></i>
    {{ $health }}
</span>
