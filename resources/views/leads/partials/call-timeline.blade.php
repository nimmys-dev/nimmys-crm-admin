{{--
    Communication history for a lead, newest first.

    Injected by CallHistoryComposer, so the Lead module does not have to know
    this module exists.

    Expects: $lead, $callTimeline
--}}

<x-dashboard-widget
    title="Call timeline"
    :subtitle="$callTimeline->isEmpty() ? null : $callTimeline->count().' most recent'"
    icon="ti ti-phone"
>
    @if ($callTimeline->isEmpty())
        <x-empty-state icon="ti ti-phone-off" message="No calls logged yet." />
    @else
        <ol class="timeline">
            @foreach ($callTimeline as $call)
                <li @class([
                    'timeline-item',
                    'is-complete' => $call->call_status->isPositive(),
                    'is-overdue' => $call->followUpUrgency() === App\Enums\FollowUpUrgency::Overdue,
                ])>
                    <span class="timeline-icon">
                        <i class="{{ $call->call_status->icon() }}" aria-hidden="true"></i>
                    </span>

                    <div class="timeline-body">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <strong>{{ $call->calledAt()->format('d-M-Y g:i A') }}</strong>
                            <x-call-status-badge :status="$call->call_status" />
                        </div>

                        <p class="m-0 text-muted text-sm">
                            Called by {{ $call->caller?->name ?? 'Unknown' }}
                            @if ($call->durationForHumans())
                                &middot; {{ $call->durationForHumans() }}
                            @endif
                        </p>

                        @if ($call->remarks)
                            {{--
                                Printed unescaped because HtmlSanitiser stripped
                                it to a safe allow-list before storage — see
                                StoreCallDetailRequest::prepareForValidation.
                            --}}
                            <div class="rich-text timeline-notes">{!! $call->remarks !!}</div>
                        @endif

                        @if ($call->next_followup_date)
                            <p class="m-0 mt-2 text-sm">
                                <span class="text-muted">Next follow-up</span>
                                <x-followup-badge :date="$call->next_followup_date" />
                            </p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</x-dashboard-widget>
