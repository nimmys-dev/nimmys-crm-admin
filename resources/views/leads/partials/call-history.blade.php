{{--
    Searchable, paginated call history plus the "log a call" modal and button.

    Injected by CallHistoryComposer.

    Expects: $lead, $callHistory, $callFilters, $callHasActiveFilters,
             $callStatusOptions, $callerOptions, $canAttributeCall
--}}

<x-card title="Call history">
    <x-slot:actions>
        <div class="flex items-center gap-3">
            <span class="text-muted text-xs font-semibold">{{ $callHistory->total() }} calls logged</span>

            @can('create', [App\Models\CallDetail::class, $lead])
                <button
                    type="button"
                    class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-sm"
                    onclick="openModal('logCallModal')"
                    style="white-space: nowrap;"
                >
                    <i class="ti ti-phone-plus"></i>
                    <span>Log Call</span>
                </button>
            @endcan
        </div>
    </x-slot:actions>

    <x-dashboard-table
        :rows="$callHistory"
        :headers="['Date', 'Time', 'Call Status', 'Interest', 'Sold / Invoice', 'Next follow-up', 'Called by', 'Remarks', ['label' => '', 'class' => 'w-px text-right']]"
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
                    @if ($call->isNotAnswered())
                        <span class="text-muted text-xs">—</span>
                    @elseif ($call->interest === true)
                        <span class="badge badge-lead-active">Interested</span>
                    @elseif ($call->interest === false)
                        <div>
                            <span class="badge badge-lead-lost">Not Interested</span>
                            @if ($call->reason)
                                <div class="text-xs text-muted mt-1" title="{{ $call->reason }}">
                                    {{ Str::limit($call->reason, 25) }}
                                </div>
                            @endif
                        </div>
                    @else
                        <span class="text-muted text-xs">—</span>
                    @endif
                </td>

                <td>
                    @if ($call->is_item_sold === true)
                        <div>
                            <span class="badge badge-lead-won">Sold</span>
                            @if ($call->invoice_number)
                                <div class="text-xs font-mono text-muted mt-1 font-semibold">{{ $call->invoice_number }}</div>
                            @endif
                            <!-- @if ($call->invoice_file_path)
                                <a href="{{ $call->invoiceUrl() }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-primary mt-1 hover:underline font-medium">
                                    <i class="ti ti-paperclip"></i> Invoice
                                </a>
                            @endif -->
                            @if ($call->invoice_file_path)
                                <a href="{{ Storage::url($call->invoice_file_path) }}"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-xs text-primary mt-1 hover:underline font-medium">
                                    <i class="ti ti-paperclip"></i> Invoice
                                </a>
                            @endif
                        </div>
                    @elseif ($call->is_item_sold === false)
                        <span class="badge badge-off">Not Sold</span>
                    @else
                        <span class="text-muted text-xs">—</span>
                    @endif
                </td>

                <td><x-followup-badge :date="$call->next_followup_date" /></td>

                <td>{{ $call->caller?->name ?? '—' }}</td>

                <td>
                    @if (filled($call->remarks))
                        <span title="{{ strip_tags($call->remarks) }}">
                            {{ Str::limit(strip_tags($call->remarks), 40) }}
                        </span>
                    @else
                        —
                    @endif
                </td>

                <td class="text-right">
                    <div class="table-actions flex items-center justify-end gap-1">
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

                        <!-- @can('delete', $call)
                            <x-button
                                variant="light" size="sm"
                                icon="ti ti-trash"
                                data-pc-toggle="modal"
                                data-pc-target="#delete-call-{{ $call->id }}"
                                aria-label="Delete call"
                            />
                        @endcan -->
                    </div>
                </td>
            </tr>
        @endforeach
    </x-dashboard-table>
</x-card>

{{-- ========================================================================= --}}
{{-- LOG CALL MODAL DIALOG                                                     --}}
{{-- ========================================================================= --}}
@can('create', [App\Models\CallDetail::class, $lead])
    <div id="logCallModal" class="custom-modal" style="display: none;">
        <div class="custom-modal-overlay" onclick="closeModal('logCallModal')"></div>

        <div class="custom-modal-dialog custom-modal-dialog-lg">
            <div class="custom-modal-header">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                        <i class="ti ti-phone-outgoing"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 text-base font-bold">Log Call Activity</h5>
                        <p class="text-xs text-muted mb-0">Record call outcome for <span class="font-semibold text-body">{{ $lead->name }}</span> ({{ $lead->reference }})</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    onclick="closeModal('logCallModal')"
                ></button>
            </div>

            <form
                method="POST"
                action="{{ route('leads.calls.store', $lead) }}"
                enctype="multipart/form-data"
                id="logCallForm"
            >
                @csrf

                <div class="custom-modal-body">
                    @include('leads.partials.call-form', [
                        'call' => new App\Models\CallDetail,
                    ])
                </div>

                <div class="custom-modal-footer bg-light/30">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        onclick="closeModal('logCallModal')"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary flex items-center gap-1.5 shadow-sm"
                    >
                        <i class="ti ti-check"></i>
                        <span>Save Call Record</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any() && old('call_status'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof openModal === 'function') {
                    openModal('logCallModal');
                }
            });
        </script>
    @endif
@endcan

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
