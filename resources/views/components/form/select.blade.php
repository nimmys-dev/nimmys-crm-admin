@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Select…',
    'required' => false,
    'hint' => null,
    'col' => 'col-span-12 md:col-span-6',
])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id') ?? 'field-' . Str::slug(str_replace('.', '-', $errorKey));
    $hasError = $errors->has($errorKey);
    $current = old($errorKey, $selected);

    // Accepts ['a' => 'A'] or a plain list ['A', 'B'], which becomes value === label.
    $options = collect($options)->mapWithKeys(
        fn ($label, $key) => [is_int($key) ? $label : $key => $label],
    );
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

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class(['form-select', 'is-invalid' => $hasError]) }}
    >
        @if ($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($hint && ! $hasError)
        <small class="text-muted">{{ $hint }}</small>
    @endif

    @error($errorKey)
        <span class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</span>
    @enderror
</div>
