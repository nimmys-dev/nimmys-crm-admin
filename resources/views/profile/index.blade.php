@extends('layouts.app')

@section('title', 'Profile')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="My profile">

                <div class="grid grid-cols-12 gap-6">

                    <div class="col-span-12 md:col-span-4 text-center">
                        <!-- <span class="w-24 h-24 mx-auto rounded-full bg-primary-500 text-white flex items-center justify-center text-[32px] font-medium">
                            {{ Str::of($user->name)->substr(0, 1)->upper() }}
                        </span> -->
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
                        @if(auth()->user()->role?->value === 'admin')
                            <x-button
                                variant="outline-secondary"
                                :href="route('settings.index')">
                                Settings
                            </x-button>
                        @endif
                         <div class="flex justify-end gap-3">
                        <x-button
                            type="button"
                            variant="primary"
                            onclick="openChangePasswordModal()">
                            <i class="ti ti-lock"></i>
                            Change Password
                        </x-button>
                                                <div
                            id="changePasswordModal"
                            class="hidden fixed inset-0 z-[9999] items-center justify-center bg-black/50"
                            onclick="closeChangePasswordModal(event)"
                        >
                            <div
                                style="width: 420px; max-width: calc(100% - 32px);"
                                class="bg-white rounded-lg shadow-xl"
                                onclick="event.stopPropagation()"
                            >

                                {{-- Header --}}
                                <div class="flex items-center justify-between px-5 py-3 border-b">
                                    <h3 class="text-base font-semibold text-gray-900">
                                        Change Password
                                    </h3>

                                    <button
                                        type="button"
                                        onclick="closeChangePasswordModal()"
                                        class="text-gray-400 hover:text-gray-600 text-lg"
                                    >
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>

                                {{-- Form --}}
                                <form action="{{ route('change-password.update') }}" method="POST">
                                    @csrf

                                    <div class="px-5 py-4">

                                        <div class="mb-3">
                                            <x-form.input
                                                type="password"
                                                name="current_password"
                                                label="Current Password"
                                                placeholder="Enter current password"
                                            />
                                        </div>

                                        <div class="mb-3">
                                            <x-form.input
                                                type="password"
                                                name="password"
                                                label="New Password"
                                                placeholder="Enter new password"
                                            />
                                        </div>

                                        <div>
                                            <x-form.input
                                                type="password"
                                                name="password_confirmation"
                                                label="Confirm Password"
                                                placeholder="Confirm new password"
                                            />
                                        </div>

                                    </div>

                                    {{-- Footer --}}
                                    <div class="flex justify-end gap-2 px-5 py-3 border-t">

                                        <x-button
                                            type="button"
                                            variant="outline-secondary"
                                            onclick="closeChangePasswordModal()"
                                        >
                                            Cancel
                                        </x-button>

                                        <x-button
                                            type="submit"
                                            variant="primary"
                                        >
                                            <i class="ti ti-lock"></i>
                                            Update Password
                                        </x-button>

                                    </div>
                                </form>

                            </div>
                        </div>
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
<script>
    function openChangePasswordModal() {
        const modal = document.getElementById('changePasswordModal');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.body.classList.add('overflow-hidden');
    }

    function closeChangePasswordModal(event = null) {

        // Close only when clicking the background
        if (event && event.target.id !== 'changePasswordModal') {
            return;
        }

        const modal = document.getElementById('changePasswordModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        document.body.classList.remove('overflow-hidden');
    }

    // ESC key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeChangePasswordModal();
        }
    });
</script>