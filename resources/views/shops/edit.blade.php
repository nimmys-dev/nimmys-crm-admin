@extends('layouts.app')

@section('title', 'Edit ' . $shop->name)

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Edit shop">

                <form method="POST" action="{{ route('shops.update', $shop) }}">
                    @csrf
                    @method('PUT')

                    @include('shops.partials.form')

                    <div class="mt-6 flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('shops.show', $shop)">Cancel</x-button>
                        <x-button type="submit" icon="ti ti-check">Save changes</x-button>
                    </div>
                </form>

            </x-card>
        </div>
    </div>

@endsection
