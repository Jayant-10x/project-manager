@props(['value' => 0, 'short' => false])

@php
    $cleanedValue = is_string($value) ? preg_replace('/[^\d.-]/', '', $value) : $value;
    $numericValue = (float) ($cleanedValue ?? 0);
@endphp

<span x-data="{ amount: {{ $numericValue }} }"
      x-text="$store.currency.format(amount, {{ $short ? 'true' : 'false' }})">
    ₹{{ number_format($numericValue, 0) }}
</span>
