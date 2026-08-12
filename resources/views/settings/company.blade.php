@extends('layouts.app')

@section('title', 'Company Profile')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-8">
            <x-card title="Letterhead">
                <p class="text-muted mb-4">
                    Printed on generated documents — currently, lead quotations.
                </p>

                <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-12 gap-4">
                        <x-form.input name="name" label="Company name" :value="$company->name" required col="col-span-12" />

                        <x-form.input name="address_line" label="Address" :value="$company->address_line" col="col-span-12" />

                        <x-form.input name="city" label="City" :value="$company->city" />
                        <x-form.input name="state" label="State" :value="$company->state" />
                        <x-form.input name="postal_code" label="Postal code" :value="$company->postal_code" />
                        <x-form.input name="country" label="Country" :value="$company->country" />

                        <x-form.input name="phone" label="Phone" :value="$company->phone" />
                        <x-form.input name="email" label="Email" type="email" :value="$company->email" />

                        <x-form.file
                            name="logo"
                            label="Logo"
                            :current-url="$logoUrl"
                            :remove-action="$logoUrl ? route('settings.company.logo.destroy') : null"
                            hint="JPG, PNG or WebP. Max 2 MB. Printed at document-header size."
                            col="col-span-12 md:col-span-6"
                        />
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-button type="submit" icon="ti ti-check">Save changes</x-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>

@endsection
