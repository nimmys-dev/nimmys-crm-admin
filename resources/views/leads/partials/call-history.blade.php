{{--
    Searchable, paginated call history plus the "log a call" form.

    Injected by CallHistoryComposer.

    Expects: $lead, $callHistory, $callFilters, $callHasActiveFilters,
             $callStatusOptions, $callerOptions, $canAttributeCall
--}}

<x-card title="Call history">
    <x-slot:actions>
        <span class="text-muted text-sm">{{ $callHistory->total() }} logged</span>
    </x-slot:actions>

    {{-- Log a call --}}
    @can('create', [App\Models\CallDetail::class, $lead])
        <details class="call-add" @if ($errors->any() && old('call_status')) open @endif>
            <summary class="call-add-toggle">
                <i class="ti ti-plus" aria-hidden="true"></i> Log a call
            </summary>

            <form method="POST" action="{{ route('leads.calls.store', $lead) }}" class="mt-4">
                @csrf

                @include('leads.partials.call-form', [
                    'call' => new App\Models\CallDetail,
                    'statusOptions' => $callStatusOptions,
                    'callerOptions' => $callerOptions,
                    'canAttribute' => $canAttributeCall,
                ])

                <div class="mt-4 flex justify-end">
                    <x-button type="submit" icon="ti ti-check">Save call</x-button>
                </div>
            </form>
        </details>
    @endcan

    {{-- Search --}}
    <form method="GET" action="{{ route('leads.show', $lead) }}" class="call-filter" role="search">
        <div class="grid grid-cols-12 gap-4 items-end">
            <div class="col-span-12 md:col-span-5">
                <label class="form-label" for="call-search">Search calls</label>
                <input
                    type="search" name="call_q" id="call-search"
                    value="{{ $callFilters['q'] }}"
                    class="form-control" placeholder="Remarks or caller name"
                />
            </div>

            <x-form.select
                name="call_status" label="Status" :options="$callStatusOptions"
                :selected="$callFilters['call_status']" placeholder="Any status"
                col="col-span-12 md:col-span-4"
            />

            <div class="col-span-12 md:col-span-3 flex items-center gap-2">
                <x-button type="submit" icon="ti ti-filter">Apply</x-button>

                @if ($callHasActiveFilters)
                    <x-button variant="outline-secondary" :href="route('leads.show', $lead)" icon="ti ti-x">Reset</x-button>
                @endif
            </div>
        </div>
    </form>

    <x-dashboard-table
        :rows="$callHistory"
        :headers="['Date', 'Time', 'Status', 'Called by', 'Next follow-up', ['label' => '', 'class' => 'w-px']]"
        :empty-message="$callHasActiveFilters
            ? 'No calls match this search.'
            : 'No calls logged for this lead yet.'"
        empty-icon="ti ti-phone-off"
    >
        @foreach ($callHistory as $call)
            <tr>
                <td>{{ $call->called_date->format('d-M-Y') }}</td>

                <td class="tabular">{{ $call->calledAt()->format('g:i A') }}</td>

                <td><x-call-status-badge :status="$call->call_status" /></td>

                <td>
                    {{ $call->caller?->name ?? '—' }}
                    @if ($call->durationForHumans())
                        <p class="m-0 text-muted text-sm">{{ $call->durationForHumans() }}</p>
                    @endif
                </td>

                <td><x-followup-badge :date="$call->next_followup_date" /></td>

                <td>
                    <div class="table-actions">
                        <x-button
                            variant="light" size="sm"
                            :href="route('leads.calls.show', [$lead, $call])"
                            icon="ti ti-eye" aria-label="View call"
                        />

                        @can('update', $call)
                            <x-button
                                variant="light" size="sm"
                                :href="route('leads.calls.edit', [$lead, $call])"
                                icon="ti ti-pencil" aria-label="Edit call"
                            />
                        @endcan

                        @can('delete', $call)
                            <x-button
                                variant="light" size="sm"
                                icon="ti ti-trash"
                                data-pc-toggle="modal"
                                data-pc-target="#delete-call-{{ $call->id }}"
                                aria-label="Delete call"
                            />
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach
    </x-dashboard-table>
</x-card>

@foreach ($callHistory as $call)
    @can('delete', $call)
        <x-delete-modal
            :id="'delete-call-' . $call->id"
            :action="route('leads.calls.destroy', [$lead, $call])"
            title="Delete call record"
            :message="'Remove the ' . $call->calledAt()->format('d-M-Y') . ' call from this history? The lead itself is kept.'"
            confirm="Remove call"
        />
    @endcan
@endforeach
