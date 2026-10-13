@props(['value' => 0, 'short' => false])

<span x-data="{ amount: {{ (float) $value }} }"
      x-text="$store.currency.format(amount, {{ $short ? 'true' : 'false' }})">
    ₹{{ number_format((float) $value, 0) }}
</span>
