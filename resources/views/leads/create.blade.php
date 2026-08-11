@extends('layouts.app')

@section('title', 'Add Lead')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Lead Form">

                <form method="POST" action="{{ route('leads.store') }}">
                    @csrf

                    @include('leads.partials.form')

                    <div class="mt-6 flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('leads.index')">Cancel</x-button>
                        <x-button type="submit" icon="ti ti-check">Create lead</x-button>
                    </div>
                </form>

            </x-card>
        </div>
    </div>

@endsection
