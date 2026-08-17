@extends('layouts.app')

@section('title', 'Edit call')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-8">
            <x-card :title="'Edit call — ' . $lead->reference">

                <form method="POST" action="{{ route('leads.calls.update', [$lead, $call]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @include('leads.partials.call-form')

                    <div class="mt-6 flex justify-end gap-3">
                        <x-button variant="outline-secondary" :href="route('leads.show', $lead)">Cancel</x-button>
                        <x-button type="submit" icon="ti ti-check">Save changes</x-button>
                    </div>
                </form>

            </x-card>
        </div>
    </div>

@endsection
