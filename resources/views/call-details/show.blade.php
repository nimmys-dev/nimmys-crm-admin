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
                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Lead</dt>
                        <dd class="m-0 mt-1 font-medium">
                            <a href="{{ route('leads.show', $lead) }}" class="text-primary hover:underline">
                                {{ $lead->name }} ({{ $lead->reference }})
                            </a>
                        </dd>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Call Status</dt>
                        <dd class="m-0 mt-1"><x-call-status-badge :status="$call->call_status" /></dd>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Called by</dt>
                        <dd class="m-0 mt-1">{{ $call->caller?->name ?? '—' }}</dd>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Call date & time</dt>
                        <dd class="m-0 mt-1">{{ $call->called_date->format('d-M-Y') }} at {{ $call->calledAt()->format('g:i A') }}</dd>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Duration</dt>
                        <dd class="m-0 mt-1">{{ $call->durationForHumans() ?? '—' }}</dd>
                    </div>

                    {{-- Decision Tree Details --}}
                    @if ($call->isAnswered())
                        <div class="col-span-12 md:col-span-6">
                            <dt class="stat-tile-label">Interest</dt>
                            <dd class="m-0 mt-1">
                                @if ($call->interest === true)
                                    <span class="badge badge-lead-active">Yes (Interested)</span>
                                @elseif ($call->interest === false)
                                    <span class="badge badge-lead-lost">No (Not Interested)</span>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>

                        @if ($call->interest === false && $call->reason)
                            <div class="col-span-12">
                                <dt class="stat-tile-label">Reason for Not Interested</dt>
                                <dd class="m-0 mt-1 font-medium text-danger-600 dark:text-danger-400">{{ $call->reason }}</dd>
                            </div>
                        @endif

                        @if ($call->interest === true)
                            <div class="col-span-12 md:col-span-6">
                                <dt class="stat-tile-label">Is item sold?</dt>
                                <dd class="m-0 mt-1">
                                    @if ($call->is_item_sold === true)
                                        <span class="badge badge-lead-won">Yes (Sold)</span>
                                    @elseif ($call->is_item_sold === false)
                                        <span class="badge badge-off">No (Not Sold)</span>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>

                            @if ($call->is_item_sold === true)
                                @if ($call->invoice_number)
                                    <div class="col-span-12 md:col-span-6">
                                        <dt class="stat-tile-label">Invoice Number</dt>
                                        <dd class="m-0 mt-1 font-mono font-semibold">{{ $call->invoice_number }}</dd>
                                    </div>
                                @endif

                                @if ($call->invoice_file_path)
                                    <div class="col-span-12 md:col-span-6">
                                        <dt class="stat-tile-label">Invoice Document</dt>
                                        <dd class="m-0 mt-1">
                                            <a href="{{ $call->invoiceUrl() }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary-700 rounded text-sm font-medium hover:bg-primary-100 transition">
                                                <i class="ti ti-download"></i> Download / View Invoice
                                            </a>
                                        </dd>
                                    </div>
                                @endif
                            @elseif ($call->is_item_sold === false)
                                <div class="col-span-12 md:col-span-6">
                                    <dt class="stat-tile-label">Next follow-up</dt>
                                    <dd class="m-0 mt-1"><x-followup-badge :date="$call->next_followup_date" /></dd>
                                </div>
                            @endif
                        @endif

                    @elseif ($call->isNotAnswered())
                        <div class="col-span-12 md:col-span-6">
                            <dt class="stat-tile-label">Next follow-up</dt>
                            <dd class="m-0 mt-1"><x-followup-badge :date="$call->next_followup_date" /></dd>
                        </div>
                    @endif

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
