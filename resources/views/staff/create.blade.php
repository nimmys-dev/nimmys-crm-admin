@extends('layouts.app')

@section('title', 'Add Staff')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Add staff member">

                {{-- enctype is required or the photo never reaches the server. --}}
                <form method="POST" action="{{ route('staff.store') }}" enctype="multipart/form-data">
                    @csrf

                    @include('staff.partials.form')

                    <div class="mt-6 flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('staff.index')">Cancel</x-button>
                        <x-button type="submit" icon="ti ti-check">Add staff member</x-button>
                    </div>
                </form>

            </x-card>
        </div>
    </div>

@endsection
