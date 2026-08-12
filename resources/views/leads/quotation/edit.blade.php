@extends('layouts.app')

@section('title', 'Quotation — ' . $lead->reference)

@section('page-actions')
    <x-button variant="outline-secondary" :href="route('leads.quotation.pdf', $lead)" icon="ti ti-download">
        Download PDF
    </x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-9">

            <form method="POST" action="{{ route('leads.quotation.update', $lead) }}">
                @csrf
                @method('PUT')

                @include('leads.quotation.partials.form')

                <div class="mt-6 flex justify-end gap-3">
                    <x-button variant="outline-secondary" :href="route('leads.show', $lead)">Cancel</x-button>
                    <x-button type="submit" icon="ti ti-check">Save changes</x-button>
                </div>
            </form>

            @can('update', $lead)
                <x-card title="Danger zone" class="mt-6">
                    <p class="text-muted mb-3">
                        Removes this quotation entirely. The lead itself is kept.
                    </p>
                    <x-button
                        variant="danger" size="sm" icon="ti ti-trash"
                        data-pc-toggle="modal" data-pc-target="#delete-quotation"
                    >
                        Delete quotation
                    </x-button>
                </x-card>
            @endcan

        </div>
    </div>

    @can('update', $lead)
        <x-delete-modal
            id="delete-quotation"
            :action="route('leads.quotation.destroy', $lead)"
            title="Delete quotation"
            :message="'Delete the quotation for ' . $lead->reference . '? This cannot be undone.'"
            confirm="Delete quotation"
        />
    @endcan

@endsection
