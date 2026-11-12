@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'bag' => 'default', 'id' => null])
@php
    $id ??= 'f-'.$name;
    $error = $errors->getBag($bag)->first($name);
@endphp
<div>
    <label for="{{ $id }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
           {{ $attributes->merge(['class' => 'field']) }}
           @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($help) aria-describedby="{{ $id }}-help" @endif>
    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-400">{{ $error }}</p>
    @elseif ($help)
        <p id="{{ $id }}-help" class="mt-1 text-xs text-muted">{{ $help }}</p>
    @endif
</div>
