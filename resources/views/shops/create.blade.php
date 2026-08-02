@extends('layouts.app')

@section('title', 'Add Shop')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Add shop">

                <form method="POST" action="{{ route('shops.store') }}">
                    @csrf

                    @include('shops.partials.form')

                    <div class="mt-6 flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('shops.index')">Cancel</x-button>
                        <x-button type="submit" icon="ti ti-check">Create shop</x-button>
                    </div>
                </form>

            </x-card>
        </div>
    </div>

@endsection
