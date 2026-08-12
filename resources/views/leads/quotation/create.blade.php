@extends('layouts.app')

@section('title', 'Quotation — ' . $lead->reference)

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-9">

            <form method="POST" action="{{ route('leads.quotation.store', $lead) }}">
                @csrf

                @include('leads.quotation.partials.form')

                <div class="mt-6 flex justify-end gap-3">
                    <x-button variant="outline-secondary" :href="route('leads.show', $lead)">Cancel</x-button>
                    <x-button type="submit" icon="ti ti-file-invoice">Create quotation</x-button>
                </div>
            </form>

        </div>
    </div>

@endsection
