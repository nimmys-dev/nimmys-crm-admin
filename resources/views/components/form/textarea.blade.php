@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 5,
    'required' => false,
    'hint' => null,
    'col' => 'col-span-12',
])

@php
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

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}
    >{{ old($errorKey, $value) }}</textarea>

    @if ($hint && ! $hasError)
        <small class="text-muted">{{ $hint }}</small>
    @endif

    @error($errorKey)
        <span class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</span>
    @enderror
</div>
