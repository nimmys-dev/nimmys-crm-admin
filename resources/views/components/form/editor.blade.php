@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 8,
    'hint' => null,
    'col' => 'col-span-12',
])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id') ?? 'field-'.Str::slug(str_replace('.', '-', $errorKey));
    $hasError = $errors->has($errorKey);
@endphp

<div class="{{ $col }}">
    @if ($label)
        <label class="form-label" for="{{ $id }}">{{ $label }}</label>
    @endif

    {{--
        Degrades to a plain textarea if TinyMCE fails to load, so the field
        still works offline or behind a strict network policy.

        The stored value is already sanitised by HtmlSanitiser on the way in,
        so the editor receives safe markup. It is still escaped here, because
        this is a textarea body — the browser unescapes it before TinyMCE
        parses it.
    --}}
    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        data-editor="rich"
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

@once
    @push('styles')
        {{-- Keeps the editor chrome from jumping before TinyMCE initialises. --}}
        <style>
            textarea[data-editor='rich'] {
                min-height: 180px;
            }
            .tox-tinymce {
                border-radius: 0.375rem !important;
            }
        </style>
    @endpush

    @push('scripts')
        {{--
            Loaded from the jsDelivr CDN. To self-host instead:

                npm i tinymce
                cp -r node_modules/tinymce public/assets/vendor/tinymce

            and swap the src below for asset('assets/vendor/tinymce/tinymce.min.js').
            Self-hosting is the better call for production — it removes a
            third-party runtime dependency and works offline.
        --}}
        <script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            (function () {
                if (typeof tinymce === 'undefined') {
                    // CDN unreachable — the plain textarea remains usable.
                    return;
                }

                var dark = document.documentElement.getAttribute('data-pc-theme') === 'dark';

                tinymce.init({
                    selector: 'textarea[data-editor="rich"]',
                    license_key: 'gpl',
                    height: 260,
                    menubar: false,
                    branding: false,
                    promotion: false,
                    plugins: 'lists link table code autolink',
                    toolbar:
                        'undo redo | blocks | bold italic underline | bullist numlist | link table | code',
                    block_formats: 'Paragraph=p; Heading=h3; Subheading=h4',

                    // Mirrors HtmlSanitiser::ALLOWED_TAGS. The server strips
                    // anything else regardless; matching here just avoids the
                    // editor offering formatting that will not survive.
                    valid_elements:
                        'p,br,strong/b,em/i,u,s,ul,ol,li,h3,h4,h5,blockquote,' +
                        'a[href|target=_blank|rel],span,table,thead,tbody,tr,th,td',

                    skin: dark ? 'oxide-dark' : 'oxide',
                    content_css: dark ? 'dark' : 'default',

                    // Push content back to the textarea on every change so a
                    // normal form submit always carries the latest value.
                    setup: function (editor) {
                        editor.on('change keyup', function () {
                            editor.save();
                        });
                    },
                });
            })();
        </script>
    @endpush
@endonce
