@props([
    'fromId' => 'date-from',
    'toId' => 'date-to',
    'fromValue' => '',
    'toValue' => '',
    'fromName' => null,
    'toName' => null,
    'fromMax' => null,
    'toMax' => null,
    'inputClass' => 'ds-input !min-h-0 !py-2 text-sm',
])

<div class="ds-date-range">
    <input
        type="date"
        id="{{ $fromId }}"
        @if ($fromName) name="{{ $fromName }}" @endif
        value="{{ $fromValue }}"
        @if ($fromMax) max="{{ $fromMax }}" @endif
        class="{{ $inputClass }} ds-date-range__input"
        aria-label="Дата начала периода"
    >
    <span class="ds-date-range__sep" aria-hidden="true"></span>
    <input
        type="date"
        id="{{ $toId }}"
        @if ($toName) name="{{ $toName }}" @endif
        value="{{ $toValue }}"
        @if ($toMax) max="{{ $toMax }}" @endif
        class="{{ $inputClass }} ds-date-range__input"
        aria-label="Дата окончания периода"
    >
</div>
