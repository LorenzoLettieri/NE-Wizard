@props([
    'model',
    'options' => [],
    'remote' => null,
    'multiple' => false,
    'live' => false,
    'placeholder' => '-- Seleziona --',
])

{{--
    TomSelect gestito da Alpine dentro wire:ignore: Livewire non tocca il DOM del widget
    e il valore passa da $wire.entangle, così la select resta allineata anche quando
    lo stesso modale viene riusato per record diversi.
    Con "remote" le opzioni arrivano da /lookups/{remote} invece di essere stampate tutte.
--}}
<div wire:ignore
    x-data="tomSelectField({
        value: $wire.entangle(@js($model)){{ $live ? '.live' : '' }},
        remoteUrl: @js($remote ? route('lookups.show', $remote) : null),
        multiple: @js((bool) $multiple),
    })"
    {{ $attributes->except(['id', 'class']) }}>
    <select x-ref="select" @if ($attributes->has('id')) id="{{ $attributes->get('id') }}" @endif
        {{ $attributes->only('class')->class(['form-select']) }} @if ($multiple) multiple @endif>
        @unless ($multiple)
            <option value="">{{ $placeholder }}</option>
        @endunless
        @foreach ($options as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</div>
