@props([
    // Set false on pages whose fields already render their own inline errors
    // (the login form), otherwise every message appears twice.
    'showErrors' => true,
])

@php
    /**
     * Renders flash messages set with ->with('success', '…') and, unless
     * suppressed, the validation error bag. Keys map to the theme's alert
     * colour variants.
     */
    $flashes = collect(['success', 'info', 'warning', 'danger', 'error'])
        ->mapWithKeys(fn ($key) => [$key === 'error' ? 'danger' : $key => session($key)])
        ->filter();
@endphp

@foreach ($flashes as $type => $message)
    <div class="alert alert-{{ $type }} flex items-start gap-3" role="alert">
        <i class="ti {{ $type === 'success' ? 'ti-circle-check' : 'ti-alert-circle' }} mt-0.5"></i>
        <span class="grow">{{ $message }}</span>
    </div>
@endforeach

@if ($showErrors && $errors->any())
    <div class="alert alert-danger" role="alert">
        <div class="flex items-start gap-3">
            <i class="ti ti-alert-circle mt-0.5"></i>
            <div class="grow">
                <p class="mb-0 font-medium">Please fix the following before continuing.</p>
                <ul class="mb-0 mt-2 list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
