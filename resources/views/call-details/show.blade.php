@extends('layouts.app')

@section('title', 'Call detail')

@section('page-actions')
    <x-button variant="outline-secondary" :href="route('leads.show', $lead)" icon="ti ti-arrow-left">Back to lead</x-button>

    @can('update', $call)
        <x-button :href="route('leads.calls.edit', [$lead, $call])" icon="ti ti-pencil">Edit</x-button>
    @endcan
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-8">
            <x-card :title="$call->calledAt()->format('d-M-Y g:i A')">
                <x-slot:actions>
                    <x-call-status-badge :status="$call->call_status" />
                </x-slot:actions>

                <dl class="grid grid-cols-12 gap-4">
                    @php
                        $details = [
                            'Lead' => $lead->name.' ('.$lead->reference.')',
                            'Call status' => $call->call_status->label(),
                            'Called by' => $call->caller?->name,
                            'Call date' => $call->called_date->format('d-M-Y'),
                            'Call time' => $call->calledAt()->format('g:i A'),
                            'Duration' => $call->durationForHumans(),
                        ];
                    @endphp

                    @foreach ($details as $label => $value)
                        <div class="col-span-12 md:col-span-6">
                            <dt class="stat-tile-label">{{ $label }}</dt>
                            <dd class="m-0 mt-1">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach

                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Next follow-up</dt>
                        <dd class="m-0 mt-1"><x-followup-badge :date="$call->next_followup_date" /></dd>
                    </div>

                    @if ($call->remarks)
                        <div class="col-span-12">
                            <dt class="stat-tile-label">Remarks</dt>
                            {{-- Sanitised on the way in; safe to render. --}}
                            <dd class="m-0 mt-1 rich-text">{!! $call->remarks !!}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>
    </div>

@endsection
