@props([
    'name',
    'label' => null,
    'accept' => 'image/*',
    'hint' => null,
    'currentUrl' => null,
    'removeAction' => null,
    'col' => 'col-span-12 md:col-span-6',
])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id') ?? 'field-'.Str::slug(str_replace('.', '-', $errorKey));
    $hasError = $errors->has($errorKey);
    $previewId = $id.'-preview';
@endphp

<div class="{{ $col }}">
    @if ($label)
        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    @endif

    <div class="flex items-start gap-4">

        {{--
            An <img src=""> makes the browser re-request the current page and
            paint a broken-image icon, which is what an empty or just-removed
            photo used to look like. Render a placeholder tile instead and let
            the preview script swap them.
        --}}
        <div class="file-preview shrink-0">
            <img
                id="{{ $previewId }}"
                @if ($currentUrl) src="{{ $currentUrl }}" @endif
                alt=""
                @unless ($currentUrl) hidden @endunless
            />
            <i class="ti ti-photo file-preview-icon" @if ($currentUrl) hidden @endif aria-hidden="true"></i>
        </div>

        <div class="grow">
            <input
                type="file"
                name="{{ $name }}"
                id="{{ $id }}"
                accept="{{ $accept }}"
                @if ($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                {{ $attributes->class(['form-control', 'is-invalid' => $hasError]) }}
            />

            @if ($hint && ! $hasError)
                <small class="text-muted">{{ $hint }}</small>
            @endif

            @error($errorKey)
                <span class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</span>
            @enderror

            @if ($currentUrl && $removeAction)
                <button
                    type="button"
                    class="btn btn-link btn-sm mt-2 text-danger"
                    data-pc-toggle="modal"
                    data-pc-target="#{{ $id }}-remove"
                >
                    Remove photo
                </button>
            @endif
        </div>

    </div>
</div>

@if ($currentUrl && $removeAction)
    {{-- Rendered outside the field so the DELETE form is never nested in the parent form. --}}
    @push('modals')
        <x-delete-modal
            :id="$id.'-remove'"
            :action="$removeAction"
            title="Remove photo"
            message="Remove this profile photo? The staff record itself is kept."
            confirm="Remove photo"
        />
    @endpush
@endif

@once
    @push('scripts')
        <script>
            // Live preview: swap the thumbnail as soon as a file is chosen,
            // so the user sees what they picked before submitting.
            document.addEventListener('change', function (event) {
                if (!event.target.matches('input[type="file"][accept^="image"]')) return;

                var preview = document.getElementById(event.target.id + '-preview');
                var file = event.target.files && event.target.files[0];
                if (!preview || !file) return;

                var placeholder = preview.parentNode.querySelector('.file-preview-icon');

                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.hidden = false;
                    if (placeholder) placeholder.hidden = true;
                };
                reader.readAsDataURL(file);
            });
        </script>
    @endpush
@endonce
