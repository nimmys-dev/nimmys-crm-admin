@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'col' => 'col-span-12 md:col-span-6',
])

@php
    // Supports dotted names (address.city) for nested validation bags.
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id') ?? 'field-' . Str::slug(str_replace('.', '-', $errorKey));
    $hasError = $errors->has($errorKey);
@endphp

<div class="{{ $col }}">
    @if ($label)
        <label class="form-label" for="{{ $id }}">
            {{ $label }}
            @if ($required)
                <span class="required" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ old($errorKey, $value) }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}
    />

    @if ($hint && ! $hasError)
        <small class="text-muted">{{ $hint }}</small>
    @endif

    @error($errorKey)
        <span class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</span>
    @enderror
</div>
