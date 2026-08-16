@extends('layouts.app')

@section('title', $lead->name)

@section('page-actions')
    <x-button variant="outline-secondary" :href="route('leads.index')" icon="ti ti-arrow-left">Back</x-button>

    @can('update', $lead)
        <x-button :href="route('leads.edit', $lead)" icon="ti ti-pencil">Edit</x-button>

        @if ($lead->status->isOpen())
            <x-button
                variant="danger"
                icon="ti ti-flag-off"
                data-pc-toggle="modal"
                data-pc-target="#close-lead-modal"
            >
                Close Lead
            </x-button>
        @endif
    @endcan
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">

        {{-- Detail column --}}
        <div class="col-span-12 xl:col-span-5">

            <x-card :title="$lead->reference">
                <x-slot:actions>
                    <x-lead-status-badge :status="$lead->status" />
                    <span class="badge {{ $lead->priority->badgeClass() }}">{{ $lead->priority->label() }}</span>
                </x-slot:actions>

                <dl class="grid grid-cols-12 gap-4">
                    @php
                        $details = [
                            'Contact' => $lead->name,
                            // 'Company' => $lead->company,
                            'Phone' => $lead->phone,
                            // 'Alternate phone' => $lead->alternate_phone,
                            // 'Email' => $lead->email,
                            // 'City' => $lead->city,
                            'Source' => $lead->source?->label(),
                            // 'Shop' => $lead->shop?->name,
                            // 'Estimated value' => filled($lead->value) ? number_format((float) $lead->value, 2) : null,
                            'Assign to' => $lead->owner?->name,
                            'Created by' => $lead->creator?->name,
                            'Last contacted' => $lead->last_contacted_at?->diffForHumans(),
                        ];
                    @endphp

                    @foreach ($details as $label => $value)
                        <div class="col-span-12 md:col-span-6">
                            <dt class="stat-tile-label">{{ $label }}</dt>
                            <dd class="m-0 mt-1">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach

                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Latest status</dt>
                        <dd class="m-0 mt-1">
                            @if ($lead->status->isClosed())
                                <span class="badge badge-off">Closed</span>
                                @if ($lead->lost_reason)
                                    <p class="m-0 mt-1 text-muted text-sm">{{ $lead->lost_reason }}</p>
                                @endif
                            @elseif ($latestCall)
                                <x-call-status-badge :status="$latestCall->call_status" />
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($lead->description)
                    <div class="rich-text mt-4">
                        <h6 class="form-section mb-3">Description</h6>
                        {{--
                            Printed unescaped because HtmlSanitiser::clean()
                            stripped it to a safe allow-list before it was
                            stored — see StoreLeadRequest::prepareForValidation.
                        --}}
                        {!! $lead->description !!}
                    </div>
                @endif

                @can('assign', $lead)
                    <div class="mt-4">
                        <h6 class="form-section mb-3">Reassign</h6>
                        <form method="POST" action="{{ route('leads.assignment.update', $lead) }}">
                            @csrf
                            @method('PUT')

                            <div class="grid grid-cols-12 gap-4 items-end">
                                <x-form.select
                                    name="assigned_to" label="Assign" :options="$assignableUsers"
                                    :selected="$lead->assigned_to" placeholder="Unassigned"
                                    col="col-span-12 sm:col-span-8"
                                />

                                <div class="col-span-12 sm:col-span-4 flex justify-end">
                                    <x-button type="submit" icon="ti ti-user-check">Reassign</x-button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endcan

                @if ($lead->quotation || Auth::user()->can('update', $lead))
                    <div class="mt-4">
                        <div class="flex items-center justify-between gap-3 mb-3 border-b border-[var(--crm-border)] pb-2">
                            <h6 class="m-0 text-xs font-bold uppercase tracking-wider text-muted">Quotation</h6>

                            <label class="quotation-toggle-label" for="quotation-toggle">
                                <span class="text-muted text-sm">{{ $lead->quotation ? 'Show' : 'Create' }}</span>
                                <span class="toggle-switch">
                                    <input type="checkbox" id="quotation-toggle" @checked($lead->quotation) />
                                    <span class="toggle-track" aria-hidden="true"></span>
                                </span>
                            </label>
                        </div>

                        <div id="quotation-panel" @unless ($lead->quotation) hidden @endunless>
                            @if ($lead->quotation)
                                <dl class="grid grid-cols-12 gap-4">
                                    <div class="col-span-12 md:col-span-6">
                                        <dt class="stat-tile-label">Reference</dt>
                                        <dd class="m-0 mt-1">{{ $lead->quotation->reference }}</dd>
                                    </div>

                                    <div class="col-span-12 md:col-span-6">
                                        <dt class="stat-tile-label">Date</dt>
                                        <dd class="m-0 mt-1">{{ $lead->quotation->issue_date->format('j M Y') }}</dd>
                                    </div>

                                    <div class="col-span-12 md:col-span-6">
                                        <dt class="stat-tile-label">Valid until</dt>
                                        <dd class="m-0 mt-1">
                                            {{ $lead->quotation->valid_until?->format('j M Y') ?? '—' }}
                                            @if ($lead->quotation->isExpired())
                                                <span class="badge badge-lead-lost">Expired</span>
                                            @endif
                                        </dd>
                                    </div>

                                    <div class="col-span-12 md:col-span-6">
                                        <dt class="stat-tile-label">Total</dt>
                                        <dd class="m-0 mt-1">{{ number_format((float) $lead->quotation->total, 2) }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-4 flex gap-3 flex-wrap">
                                    @can('update', $lead)
                                        <x-button :href="route('leads.quotation.edit', $lead)" icon="ti ti-pencil">
                                            Edit quotation
                                        </x-button>
                                    @endcan

                                    <x-button
                                        variant="outline-secondary" :href="route('leads.quotation.pdf', $lead)"
                                        icon="ti ti-download"
                                    >
                                        Download PDF
                                    </x-button>
                                </div>
                            @else
                                <p class="text-muted mb-3">
                                    Prepare a price quotation for {{ $lead->name }} — customer address, items,
                                    quantity and rate.
                                </p>

                                @can('update', $lead)
                                    <x-button :href="route('leads.quotation.create', $lead)" icon="ti ti-file-invoice">
                                        Create quotation
                                    </x-button>
                                @endcan
                            @endif
                        </div>
                    </div>

                    @push('scripts')
                        <script>
                            (function () {
                                var toggle = document.getElementById('quotation-toggle');
                                var panel = document.getElementById('quotation-panel');
                                if (!toggle || !panel) return;

                                toggle.addEventListener('change', function () {
                                    panel.hidden = !this.checked;
                                });
                            })();
                        </script>
                    @endpush
                @endif
            </x-card>

        </div>
        {{-- call history --}}
        <div class="col-span-12 xl:col-span-7">
            {{-- Call Details module. Data supplied by CallHistoryComposer. --}}
            @includeWhen(isset($callHistory), 'leads.partials.call-history')
        </div>
        {{-- <div class="col-span-12 xl:col-span-5">
            @includeWhen(isset($callTimeline), 'leads.partials.call-timeline')
        </div> --}}

        {{-- Follow-up column --}}
        {{-- <div class="col-span-12 xl:col-span-5">

            @can('addFollowUp', $lead)
                <x-card title="Log or schedule a follow-up">
                    <form method="POST" action="{{ route('leads.follow-ups.store', $lead) }}">
                        @csrf

                        <div class="grid grid-cols-12 gap-4">
                            <x-form.select
                                name="type" label="Type" :options="$followUpTypes"
                                :placeholder="false" required col="col-span-12 md:col-span-6"
                            /> --}}

                            {{-- <x-form.input --}}
                                {{-- Plain &, not &amp;: Blade escapes {{ $label }} on output, so a
                                     pre-escaped entity here renders literally as "&amp;". --}}
                                {{-- name="scheduled_at" type="datetime-local" label="Date & time"
                                col="col-span-12 md:col-span-6" --}}
                            {{-- /> --}}

                            {{-- <x-form.textarea name="notes" label="Notes" rows="3" col="col-span-12" />

                            <div class="col-span-12">
                                <x-form.toggle
                                    name="log_now"
                                    label="Already done"
                                    hint="Tick to record a completed contact instead of scheduling one."
                                    col="col-span-12"
                                />
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <x-button type="submit" icon="ti ti-plus">Save follow-up</x-button>
                        </div>
                    </form>
                </x-card>
            @endcan
        </div> --}}

        {{-- <div class="col-span-12 xl:col-span-5">
            <x-dashboard-widget title="Follow-up history" icon="ti ti-timeline">
                @if ($lead->followUps->isEmpty())
                    <x-empty-state icon="ti ti-calendar-off" message="No follow-ups recorded yet." />
                @else
                    <ol class="timeline">
                        @foreach ($lead->followUps as $followUp)
                            <li @class(['timeline-item', 'is-overdue' => $followUp->isOverdue(), 'is-complete' => $followUp->isComplete()])>
                                <span class="timeline-icon"><i class="{{ $followUp->type->icon() }}"></i></span>

                                <div class="timeline-body">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <strong>{{ $followUp->type->label() }}</strong>

                                        @if ($followUp->isComplete())
                                            <span class="badge badge-on">Done</span>
                                        @elseif ($followUp->isOverdue())
                                            <span class="badge badge-lead-lost">Overdue</span>
                                        @else
                                            <span class="badge badge-lead-new">Scheduled</span>
                                        @endif
                                    </div>

                                    <p class="m-0 text-muted text-sm">
                                        {{ $followUp->scheduled_at?->format('j M Y, g:i a') ?? '—' }}
                                        @if ($followUp->user) &middot; {{ $followUp->user->name }} @endif
                                    </p>

                                    @if ($followUp->notes)
                                        <p class="timeline-notes">{{ $followUp->notes }}</p>
                                    @endif

                                    @if ($followUp->outcome)
                                        <p class="m-0 text-sm"><em>{{ $followUp->outcome }}</em></p>
                                    @endif

                                    @if (! $followUp->isComplete())
                                        @can('addFollowUp', $lead)
                                            <form method="POST"
                                                action="{{ route('leads.follow-ups.complete', [$lead, $followUp]) }}"
                                                class="mt-2">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-light btn-sm">
                                                    <i class="ti ti-check"></i> Mark done
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-dashboard-widget>

        </div> --}}

    </div>

    @can('update', $lead)
        @if ($lead->status->isOpen())
            @push('modals')
                <div class="modal" id="close-lead-modal" role="dialog" aria-modal="true" aria-labelledby="close-lead-modal-title">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('leads.close', $lead) }}">
                                @csrf
                                @method('PATCH')

                                <div class="modal-header">
                                    <h5 id="close-lead-modal-title">Close lead</h5>
                                    <button type="button" class="modal-close" data-pc-modal-dismiss="#close-lead-modal" aria-label="Close">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>

                                <div class="modal-body">
                                    <div class="grid grid-cols-12 gap-4">
                                        <x-form.select
                                            name="status"
                                            label="Outcome"
                                            :options="$closeOptions"
                                            placeholder="Select outcome"
                                            required
                                            col="col-span-12"
                                        />

                                        <x-form.input
                                            name="lost_reason"
                                            label="Reason"
                                            hint="Required for either outcome."
                                            required
                                            col="col-span-12"
                                        />
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-pc-modal-dismiss="#close-lead-modal">Cancel</button>
                                    <x-button type="submit" variant="danger" icon="ti ti-flag-off">Close lead</x-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endpush
        @endif
    @endcan

@endsection
