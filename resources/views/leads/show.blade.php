@extends('layouts.app')

@section('title', $lead->name)

@section('page-actions')
    <x-button variant="outline-secondary" :href="route('leads.index')" icon="ti ti-arrow-left">Back</x-button>

    @can('update', $lead)
        <x-button :href="route('leads.edit', $lead)" icon="ti ti-pencil">Edit</x-button>
    @endcan
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">

        {{-- Detail column --}}
        <div class="col-span-12 xl:col-span-7">

            <x-card :title="$lead->reference">
                <x-slot:actions>
                    <x-lead-status-badge :status="$lead->status" />
                    <span class="badge {{ $lead->priority->badgeClass() }}">{{ $lead->priority->label() }}</span>
                </x-slot:actions>

                <dl class="grid grid-cols-12 gap-4">
                    @php
                        $details = [
                            'Contact' => $lead->name,
                            'Company' => $lead->company,
                            'Phone' => $lead->phone,
                            'Alternate phone' => $lead->alternate_phone,
                            'Email' => $lead->email,
                            'City' => $lead->city,
                            'Source' => $lead->source?->label(),
                            'Shop' => $lead->shop?->name,
                            'Estimated value' => filled($lead->value) ? number_format((float) $lead->value, 2) : null,
                            'Owner' => $lead->owner?->name,
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

                    @if ($lead->status === App\Enums\LeadStatus::Lost && $lead->lost_reason)
                        <div class="col-span-12">
                            <dt class="stat-tile-label">Reason lost</dt>
                            <dd class="m-0 mt-1">{{ $lead->lost_reason }}</dd>
                        </div>
                    @endif
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
            </x-card>

            @can('assign', $lead)
                <x-card title="Assignment">
                    <form method="POST" action="{{ route('leads.assignment.update', $lead) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-12 gap-4 items-end">
                            <x-form.select
                                name="assigned_to" label="Owner" :options="$assignableUsers"
                                :selected="$lead->assigned_to" placeholder="Unassigned"
                                col="col-span-12 md:col-span-8"
                            />

                            <div class="col-span-12 md:col-span-4">
                                <x-button type="submit" icon="ti ti-user-check">Reassign</x-button>
                            </div>
                        </div>
                    </form>
                </x-card>
            @endcan

            {{-- Call Details module. Data supplied by CallHistoryComposer. --}}
            @includeWhen(isset($callHistory), 'leads.partials.call-history')

        </div>

        {{-- Follow-up column --}}
        <div class="col-span-12 xl:col-span-5">

            @includeWhen(isset($callTimeline), 'leads.partials.call-timeline')

            @can('addFollowUp', $lead)
                <x-card title="Log or schedule a follow-up">
                    <form method="POST" action="{{ route('leads.follow-ups.store', $lead) }}">
                        @csrf

                        <div class="grid grid-cols-12 gap-4">
                            <x-form.select
                                name="type" label="Type" :options="$followUpTypes"
                                :placeholder="false" required col="col-span-12 md:col-span-6"
                            />

                            <x-form.input
                                {{-- Plain &, not &amp;: Blade escapes {{ $label }} on output, so a
                                     pre-escaped entity here renders literally as "&amp;". --}}
                                name="scheduled_at" type="datetime-local" label="Date & time"
                                col="col-span-12 md:col-span-6"
                            />

                            <x-form.textarea name="notes" label="Notes" rows="3" col="col-span-12" />

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

        </div>

    </div>

@endsection
