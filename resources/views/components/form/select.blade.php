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

    $options = $options instanceof \Illuminate\Support\Collection
        ? $options->all()
        : (array) $options;

    /**
     * Only a genuine list (['Low', 'High'], keys 0..n-1) means value === label.
     * Any keyed map uses its own keys as the submitted values — including
     * integer keys, which are model IDs, not positions.
     *
     * Checking is_int($key) here instead would silently post the label for
     * every ID => name dropdown.
     */
    if (array_is_list($options)) {
        $options = array_combine($options, $options);
    }
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
