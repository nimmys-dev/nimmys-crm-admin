@extends('layouts.app')

@section('title', 'Edit ' . $lead->reference)

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card :title="'Edit ' . $lead->reference">

                <form method="POST" action="{{ route('leads.update', $lead) }}">
                    @csrf
                    @method('PUT')

                    @include('leads.partials.form')

                    <div class="mt-6 flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('leads.show', $lead)">Cancel</x-button>
                        <x-button type="submit" icon="ti ti-check">Save changes</x-button>
                    </div>
                </form>

            </x-card>
        </div>
    </div>

@endsection
