<span class="{{ $class }} {{ $warn ? 'warn' : '' }}"
      @if($poll) wire:poll.{{ $poll }} @endif
      @if($hideZero && $this->count === 0) style="display: none !important;" @endif>
    {{ $this->count }}
</span>
