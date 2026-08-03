@extends('layouts.app')

@section('title', 'Edit ' . $staff->name)

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Edit staff member">

                <form method="POST" action="{{ route('staff.update', $staff) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @include('staff.partials.form')

                    <div class="mt-6 flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('staff.show', $staff)">Cancel</x-button>
                        <x-button type="submit" icon="ti ti-check">Save changes</x-button>
                    </div>
                </form>

            </x-card>
        </div>
    </div>

@endsection
