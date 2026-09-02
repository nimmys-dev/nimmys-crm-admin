@extends('layouts.app')

@section('title', $lead->name)

@section('page-actions')
<div class="page-actions-responsive">
    <x-button variant="outline-secondary" :href="route('leads.index')" icon="ti ti-arrow-left">Back</x-button>

    @can('update', $lead)
        <x-button :href="route('leads.edit', $lead)" icon="ti ti-pencil">Edit</x-button>

        @can('create', [App\Models\CallDetail::class, $lead])
            <button
                type="button"
                class="btn btn-primary"
                onclick="openModal('logCallModal')"
                style="white-space: nowrap;"
            >
                <i class="ti ti-phone-plus me-1"></i>
                Log Call
            </button>
        @endcan
        <!-- @if ($lead->status->isOpen())
            <x-button
                variant="danger"
                icon="ti ti-flag-off"
                data-pc-toggle="modal"
                data-pc-target="#close-lead-modal"
            >
                Close Lead
            </x-button>
        @endif -->
        {{-- Activity Log --}}
        <button
            type="button"
            class="btn btn-outline-primary"
            onclick="openModal('activityLogModal')"
            style="white-space: nowrap;"
        >
            <i class="ti ti-history me-1"></i>
            Activity Log
        </button>

        {{-- Reassign --}}
        <!-- permission @can('assign', $lead) @endcan-->

            <button
                type="button"
                class="btn btn-primary"
                onclick="openModal('reassignModal')"
                style="white-space: nowrap;"
            >
                <i class="ti ti-user-check me-1"></i>
                Reassign
            </button>
         {{-- Quotation --}}
        @if ($lead->quotation || Auth::user()->can('update', $lead))

            <button
                type="button"
                class="btn btn-primary"
                onclick="openModal('quotationModal')"
                style="white-space: nowrap;"
            >
                <i class="ti ti-file-invoice me-1"></i>
                Quotation
            </button>

        @endif
    @endcan
</div>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">

        {{-- Detail column --}}
        <div class="col-span-12 xl:col-span-5">

            <x-card :title="$lead->reference">

                <x-slot:actions>
                    <x-lead-status-badge :status="$lead->status" />

                    <span class="badge {{ $lead->priority->badgeClass() }}">
                        {{ $lead->priority->label() }}
                    </span>
                </x-slot:actions>

                    <dl class="grid grid-cols-12 gap-4">

                        @php
                            $details = [
                                'Contact' => $lead->name,
                                'Phone' => $lead->phone,
                                'Source' => $lead->source?->label(),
                                'Assign to' => $lead->owner?->name,
                                'Created by' => $lead->creator?->name,
                                'Last contacted' => $lead->last_contacted_at?->diffForHumans(),
                            ];
                        @endphp

                        {{-- Lead Details --}}
                        @foreach ($details as $label => $value)
                            <div class="col-span-12 md:col-span-4 lg:col-span-4">
                                <div
                                    class="lead-detail-item"
                                    style="
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                        padding: 14px 16px;
                                        background: #fff;
                                        min-height: 75px;
                                    "
                                >
                                    <dt
                                        class="stat-tile-label"
                                        style="
                                            font-size: 12px;
                                            font-weight: 600;
                                            color: #6b7280;
                                            margin-bottom: 5px;
                                        "
                                    >
                                        {{ $label }}
                                    </dt>

                                    <dd
                                        class="m-0 lead-detail-value"
                                        style="
                                            font-size: 14px;
                                            font-weight: 500;
                                            color: #111827;
                                            line-height: 1.4;
                                        "
                                    >
                                        {{ $value ?: '—' }}
                                    </dd>
                                </div>
                            </div>
                        @endforeach


                        {{-- Latest Status (4 Columns) --}}
                        <div class="col-span-12 md:col-span-4 lg:col-span-4">
                            <div
                                class="lead-detail-item"
                                style="
                                    border: 1px solid #e5e7eb;
                                    border-radius: 8px;
                                    padding: 14px 16px;
                                    background: #fff;
                                    min-height: 75px;
                                    height: 100%;
                                "
                            >
                                <dt
                                    class="stat-tile-label"
                                    style="
                                        font-size: 12px;
                                        font-weight: 600;
                                        color: #6b7280;
                                        margin-bottom: 5px;
                                    "
                                >
                                    Latest status
                                </dt>

                                <dd
                                    class="m-0"
                                    style="
                                        font-size: 14px;
                                        font-weight: 500;
                                        color: #111827;
                                    "
                                >
                                    @if ($lead->status->isClosed())
                                        <span class="badge badge-off">
                                            Closed
                                        </span>

                                        @if ($lead->lost_reason)
                                            <p class="m-0 mt-1 text-muted text-sm">
                                                {{ $lead->lost_reason }}
                                            </p>
                                        @endif
                                    @elseif ($latestCall)
                                        <x-call-status-badge
                                            :status="$latestCall->call_status"
                                        />
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                        </div>

                        {{-- Description (Full Remaining 8 Columns) --}}
                        @if ($lead->description)
                            <div class="col-span-12 md:col-span-8 lg:col-span-8">
                                <div
                                    class="lead-detail-item w-full"
                                    style="
                                        border: 1px solid #e5e7eb;
                                        border-radius: 8px;
                                        padding: 14px 16px;
                                        background: #fff;
                                        min-height: 75px;
                                        max-height: 140px;
                                        overflow-y: auto;
                                    "
                                >
                                    <dt
                                        class="stat-tile-label"
                                        style="
                                            font-size: 12px;
                                            font-weight: 600;
                                            color: #6b7280;
                                            margin-bottom: 5px;
                                        "
                                    >
                                        Description
                                    </dt>

                                    <dd
                                        class="m-0 lead-detail-value"
                                        style="
                                            font-size: 14px;
                                            font-weight: 500;
                                            color: #111827;
                                            line-height: 1.6;
                                            white-space: normal;
                                            word-break: break-word;
                                            overflow-wrap: anywhere;
                                        "
                                    >
                                        {!! $lead->description !!}
                                    </dd>
                                </div>
                            </div>
                        @endif

                        <div id="reassignModal" class="custom-modal"style="display:none;">
                            <div class="custom-modal-overlay" onclick="closeModal('reassignModal')"></div>

                            <div class="custom-modal-dialog">

                                <div class="custom-modal-header">

                                    <h5 class="mb-0">
                                        Reassign Lead
                                    </h5>

                                    <button
                                        type="button"
                                        class="btn-close"
                                        onclick="closeModal('reassignModal')"
                                    ></button>

                                </div>

                                <form
                                    method="POST"
                                    action="{{ route('leads.assignment.update', $lead) }}"
                                >
                                    @csrf
                                    @method('PUT')

                                    <div class="custom-modal-body">

                                        <x-form.select
                                            name="assigned_to"
                                            :options="$assignableUsers"
                                            :selected="$lead->assigned_to"
                                            placeholder="Unassigned"
                                            col="col-span-12"
                                        />

                                    </div>

                                    <div class="custom-modal-footer">

                                        <button
                                            type="button"
                                            class="btn btn-secondary"
                                            onclick="closeModal('reassignModal')"
                                        >
                                            Cancel
                                        </button>

                                        <button
                                            type="submit"
                                            class="btn btn-primary"
                                        >
                                            <i class="ti ti-user-check me-1"></i>
                                            Reassign
                                        </button>

                                    </div>

                                </form>

                            </div>
                        </div>
                        <div id="quotationModal" class="custom-modal"style="display:none;">
                            <div class="custom-modal-overlay" onclick="closeModal('quotationModal')"></div>

                            <div class="custom-modal-dialog">

                                <div class="custom-modal-header">

                                    <h5 class="mb-0">
                                        Quotation
                                    </h5>

                                    <button
                                        type="button"
                                        class="btn-close"
                                        onclick="closeModal('quotationModal')"
                                    ></button>

                                </div>

                                <div class="custom-modal-body">

                                    @if ($lead->quotation)

                                        <dl class="grid grid-cols-12 gap-4">

                                            <!-- Reference -->
                                            <div class="col-span-6">
                                                <dt class="stat-tile-label">
                                                    Reference
                                                </dt>
                                                <dd class="mt-1 fw-bold">
                                                    {{ $lead->quotation->reference }}
                                                </dd>
                                            </div>

                                            <!-- Total (Aligned Right) -->
                                            <div class="col-span-6 text-end">
                                                <dt class="stat-tile-label">
                                                    Total
                                                </dt>
                                                <dd class="mt-1 fw-bold">
                                                    {{ number_format((float) $lead->quotation->total, 2) }}
                                                </dd>
                                            </div>

                                        </dl>

                                    @else

                                        <p class="text-muted mb-0">
                                            No quotation has been created for this lead yet.
                                        </p>

                                    @endif

                                </div>

                                <div class="custom-modal-footer">

                                    <button
                                        type="button"
                                        class="btn btn-secondary"
                                        onclick="closeModal('quotationModal')"
                                    >
                                        Close
                                    </button>

                                    @if ($lead->quotation)

                                        @can('update', $lead)
                                            <a
                                                href="{{ route('leads.quotation.edit', $lead) }}"
                                                class="btn btn-primary"
                                            >
                                                <i class="ti ti-pencil me-1"></i>
                                                Edit Quotation
                                            </a>
                                        @endcan

                                        <a
                                            href="{{ route('leads.quotation.pdf', $lead) }}"
                                            class="btn btn-outline-secondary"
                                        >
                                            <i class="ti ti-download me-1"></i>
                                            Download PDF
                                        </a>

                                    @else

                                        @can('update', $lead)
                                            <a
                                                href="{{ route('leads.quotation.create', $lead) }}"
                                                class="btn btn-primary"
                                            >
                                                <i class="ti ti-file-invoice me-1"></i>
                                                Create Quotation
                                            </a>
                                        @endcan

                                    @endif

                                </div>

                            </div>
                        </div>
                        @if ($lead->quotation || Auth::user()->can('update', $lead))

                        @push('scripts')

                            <script>

                                (function () {

                                    var toggle =
                                        document.getElementById('quotation-toggle');

                                    var panel =
                                        document.getElementById('quotation-panel');

                                    if (!toggle || !panel) {
                                        return;
                                    }

                                    toggle.addEventListener('change', function () {

                                        panel.hidden = !this.checked;

                                    });

                                })();

                            </script>

                        @endpush

                    @endif
                    </dl>
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

    {{-- Activity Log Modal --}}
    @include('leads.partials.activity-log-modal')

@endsection

<script>
    function openModal(id) {
        const modal = document.getElementById(id);

        if (!modal) {
            console.error('Modal not found:', id);
            return;
        }

        modal.classList.add('show');
        modal.style.display = 'flex';

        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        const modal = document.getElementById(id);

        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        modal.style.display = 'none';

        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.custom-modal.show').forEach(function (modal) {
                closeModal(modal.id);
            });
        }
    });
</script>
