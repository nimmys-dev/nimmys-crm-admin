@extends('layouts.app')

@section('title', 'Settings')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-8">
            <x-card title="Application">
                <div class="grid grid-cols-12 gap-4">
                    <x-form.input name="app_name" label="Application name" :value="config('app.name')" readonly />
                    <x-form.input name="timezone" label="Timezone" :value="config('app.timezone')" readonly />
                </div>
            </x-card>

            <x-card title="Company Profile">
                <p class="text-muted mb-4">
                    The letterhead — name, address, contact details and logo — printed on
                    generated documents such as lead quotations.
                </p>
                <x-button variant="outline-secondary" :href="route('settings.company.edit')" icon="ti ti-building">
                    Edit company profile
                </x-button>
            </x-card>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <x-card title="Appearance">
                <p class="text-muted mb-4">Theme preference is stored in this browser.</p>
                <div class="flex gap-3">
                    <x-button variant="outline-secondary" icon="ti ti-sun" onclick="layout_change('light')">Light</x-button>
                    <x-button variant="outline-secondary" icon="ti ti-moon" onclick="layout_change('dark')">Dark</x-button>
                </div>
            </x-card>
        </div>
    </div>

@endsection
