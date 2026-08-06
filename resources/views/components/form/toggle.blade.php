@props([
    'name',
    'label' => null,
    'checked' => false,
    'hint' => null,
    'disabled' => false,
    'col' => 'col-span-12 md:col-span-6',
])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id') ?? 'field-'.Str::slug(str_replace('.', '-', $errorKey));
    $hasError = $errors->has($errorKey);
    $isOn = (bool) old($errorKey, $checked);
@endphp

<div class="{{ $col }}">
    <div class="toggle-field">

        {{--
            The hidden input makes an unticked box post an explicit "0".
            Without it the key is simply absent, which reads as "unchanged"
            rather than "switched off" and makes the toggle impossible to
            turn back off. Disabled inputs post nothing at all, so it is
            omitted in that case and the stored value stands.
        --}}
        @unless ($disabled)
            <input type="hidden" name="{{ $name }}" value="0" />
        @endunless

        <label class="toggle-switch" for="{{ $id }}">
            <input
                type="checkbox"
                name="{{ $name }}"
                id="{{ $id }}"
                value="1"
                @checked($isOn)
                @disabled($disabled)
                {{ $attributes->except('id') }}
            />
            <span class="toggle-track" aria-hidden="true"></span>
        </label>

        <div class="toggle-copy">
            @if ($label)
                <label class="form-label mb-0" for="{{ $id }}">{{ $label }}</label>
            @endif

            @if ($disabled)
                <small class="text-muted">Only an Admin can change this.</small>
            @elseif ($hint && ! $hasError)
                <small class="text-muted">{{ $hint }}</small>
            @endif

            @error($errorKey)
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

    </div>
</div>
