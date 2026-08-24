@extends('layouts.app')

@section('title', 'Profile')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="My profile">

                <div class="grid grid-cols-12 gap-6">

                    <div class="col-span-12 md:col-span-4 text-center">
                        <span class="w-24 h-24 mx-auto rounded-full bg-primary-500 text-white flex items-center justify-center text-[32px] font-medium">
                            {{ Str::of($user->name)->substr(0, 1)->upper() }}
                        </span>
                        <h5 class="mt-4 mb-1">{{ $user->name }}</h5>
                        <p class="text-muted mb-3">{{ $user->email }}</p>
                        <x-status-badge status="active" />
                    </div>

                    <div class="col-span-12 md:col-span-8">
                        <div class="grid grid-cols-12 gap-4">
                            <x-form.input name="name" label="Full name" :value="$user->name" readonly />
                            <x-form.input name="email" type="email" label="Email" :value="$user->email" readonly />
                            <x-form.input
                                name="joined_at"
                                label="Joined"
                                :value="$user->created_at?->format('j M Y')"
                                readonly
                            />
                            <x-form.input
                                name="email_verified"
                                label="Email verified"
                                :value="$user->email_verified_at ? 'Yes' : 'No'"
                                readonly
                            />
                        </div>
                    </div>

                </div>

                <x-slot:footer>
                    <div class="flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('settings.index')">Settings</x-button>
                        <x-button disabled>Edit profile</x-button>

                     <x-button
    variant="primary"
    :href="route('my-tasks.index')">
    <i class="ti ti-list-check"></i>
    Tasks
</x-button>
                    </div>
                </x-slot:footer>

            </x-card>
        </div>
    </div>

@endsection
