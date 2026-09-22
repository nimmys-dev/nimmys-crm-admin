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
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
        new TomSelect('select[name="assigned_to"]', {
        create: false,
        allowEmptyOption: true,
        placeholder: 'Unassigned',
        searchField: ['text'],
        maxOptions: null
    });

});
</script>