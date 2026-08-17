{{--
    Activity Log & Audit Trail Modal

    Renders a vertical visual timeline of every activity recorded on the lead
    from inception to closure.

    Expects: $lead, $activities
--}}

@php
    $activitiesList = $activities ?? $lead->activities()->with('user:id,name,role')->latest('id')->get();
@endphp

<div id="activityLogModal" class="custom-modal" style="display: none;">
    <div class="custom-modal-overlay" onclick="closeModal('activityLogModal')"></div>

    <div class="custom-modal-dialog custom-modal-dialog-lg">
        {{-- Modal Header --}}
        <div class="custom-modal-header bg-light/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl shadow-sm">
                    <i class="ti ti-history"></i>
                </div>
                <div>
                    <h5 class="mb-0 text-base font-bold text-dark dark:text-light">
                        Activity Log & Audit Trail
                    </h5>
                    <p class="text-xs text-muted mb-0">
                        History for <span class="font-semibold text-body">{{ $lead->name }}</span> ({{ $lead->reference }}) • {{ $activitiesList->count() }} events
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="btn-close"
                onclick="closeModal('activityLogModal')"
                aria-label="Close"
            ></button>
        </div>

        {{-- Modal Body --}}
        <div class="custom-modal-body space-y-4">
            {{-- Filter Chips --}}
            <div class="activity-filter-tabs">
                <button
                    type="button"
                    class="activity-filter-btn active"
                    data-filter="all"
                    onclick="filterActivities('all', this)"
                >
                    <i class="ti ti-list-details me-1"></i> All Activities ({{ $activitiesList->count() }})
                </button>

                <button
                    type="button"
                    class="activity-filter-btn"
                    data-filter="call"
                    onclick="filterActivities('call', this)"
                >
                    <i class="ti ti-phone-incoming me-1"></i> Calls
                </button>

                <button
                    type="button"
                    class="activity-filter-btn"
                    data-filter="quotation"
                    onclick="filterActivities('quotation', this)"
                >
                    <i class="ti ti-file-invoice me-1"></i> Quotations
                </button>

                <button
                    type="button"
                    class="activity-filter-btn"
                    data-filter="assignment"
                    onclick="filterActivities('assignment', this)"
                >
                    <i class="ti ti-user-check me-1"></i> Assignments
                </button>

                <button
                    type="button"
                    class="activity-filter-btn"
                    data-filter="status"
                    onclick="filterActivities('status', this)"
                >
                    <i class="ti ti-flag me-1"></i> Status & Closure
                </button>
            </div>

            {{-- Timeline Container --}}
            @if ($activitiesList->isNotEmpty())
                <div class="activity-timeline" id="activityTimelineList">
                    @foreach ($activitiesList as $activity)
                        @php
                            $actor = $activity->user;
                            $actorRole = $actor?->role?->label() ?? 'System';
                            $actorName = $actor?->name ?? 'System';
                            $category = $activity->category();
                            $props = $activity->properties ?? [];
                        @endphp

                        <div class="activity-item" data-category="{{ $category }}">
                            {{-- Icon Dot --}}
                            <div class="activity-dot {{ $activity->iconColorClass() }}">
                                <i class="{{ $activity->icon() }}"></i>
                            </div>

                            {{-- Activity Card --}}
                            <div class="activity-card">
                                <div class="flex items-start justify-between gap-3 mb-1.5">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        {{-- Actor --}}
                                        <span class="font-semibold text-sm text-dark dark:text-light">
                                            {{ $actorName }}
                                        </span>

                                        <span class="badge badge-subtle-primary text-xs py-0.5 px-2">
                                            {{ $actorRole }}
                                        </span>

                                        {{-- Action Type Badge --}}
                                        <span class="activity-meta-badge">
                                            <i class="{{ $activity->icon() }}"></i>
                                            {{ $activity->typeLabel() }}
                                        </span>
                                    </div>

                                    {{-- Timestamp --}}
                                    <div class="text-right flex-shrink-0">
                                        <span class="text-xs font-semibold text-muted" title="{{ $activity->created_at->format('d-M-Y h:i:s A') }}">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </span>
                                        <div class="text-xs text-muted/70 font-mono">
                                            {{ $activity->created_at->format('d-M-Y, g:i A') }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Description --}}
                                <p class="text-sm text-body mb-2 leading-relaxed">
                                    {{ $activity->description }}
                                </p>

                                {{-- Structured Details --}}
                                @if (!empty($props))
                                    <div class="mt-2 pt-2 border-t border-dashed border-gray-200 dark:border-dark-700 text-xs space-y-1.5">
                                        {{-- Call Details --}}
                                        @if (isset($props['call_status']))
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="activity-meta-badge">
                                                    <strong>Status:</strong> {{ $props['call_status'] }}
                                                </span>

                                                @if (isset($props['interest']))
                                                    <span class="activity-meta-badge">
                                                        <strong>Interest:</strong> {{ $props['interest'] ? 'Yes' : 'No' }}
                                                    </span>
                                                @endif

                                                @if (isset($props['invoice_number']))
                                                    <span class="activity-meta-badge text-success">
                                                        <i class="ti ti-receipt"></i> <strong>Invoice:</strong> {{ $props['invoice_number'] }}
                                                    </span>
                                                @endif

                                                @if (isset($props['next_followup_date']))
                                                    <span class="activity-meta-badge text-primary">
                                                        <i class="ti ti-calendar"></i> <strong>Next Follow-up:</strong> {{ $props['next_followup_date'] }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if (!empty($props['remarks']))
                                                <div class="bg-light/60 dark:bg-dark-800 p-2 rounded text-muted mt-1 italic">
                                                    "{{ Str::limit($props['remarks'], 150) }}"
                                                </div>
                                            @endif
                                        @endif

                                        {{-- Quotation Details --}}
                                        @if (isset($props['quotation_number']))
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="activity-meta-badge text-warning font-semibold">
                                                    <i class="ti ti-file-invoice"></i> #{{ $props['quotation_number'] }}
                                                </span>

                                                @if (isset($props['total_amount']))
                                                    <span class="activity-meta-badge text-success font-bold">
                                                        ₹{{ number_format((float) $props['total_amount'], 2) }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Assignment Details --}}
                                        @if (isset($props['from_user_name']) && isset($props['to_user_name']))
                                            <div class="flex items-center gap-1.5 text-muted">
                                                <i class="ti ti-arrow-right text-primary"></i>
                                                <span>Transferred from <strong>{{ $props['from_user_name'] }}</strong> to <strong>{{ $props['to_user_name'] }}</strong></span>
                                            </div>
                                        @endif

                                        {{-- Closure Details --}}
                                        @if (isset($props['reason']) && filled($props['reason']))
                                            <div class="text-danger flex items-center gap-1">
                                                <i class="ti ti-info-circle"></i>
                                                <span>Reason: <strong>{{ $props['reason'] }}</strong></span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Filter Empty Placeholder --}}
                <div id="activityFilterEmpty" class="text-center py-8 text-muted" style="display: none;">
                    <i class="ti ti-filter-off text-3xl mb-2 block"></i>
                    <p class="text-sm mb-0">No activities found in this category.</p>
                </div>
            @else
                <div class="text-center py-12 text-muted">
                    <div class="w-14 h-14 rounded-full bg-light flex items-center justify-center mx-auto mb-3 text-2xl">
                        <i class="ti ti-history-off"></i>
                    </div>
                    <h6 class="font-semibold text-base mb-1">No activities logged yet</h6>
                    <p class="text-xs">Activities will automatically appear here as actions are taken on this lead.</p>
                </div>
            @endif
        </div>

        {{-- Modal Footer --}}
        <div class="custom-modal-footer bg-light/30">
            <button
                type="button"
                class="btn btn-secondary"
                onclick="closeModal('activityLogModal')"
            >
                Close
            </button>
        </div>
    </div>
</div>

<script>
window.filterActivities = function(category, btn) {
    document.querySelectorAll('.activity-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const items = document.querySelectorAll('#activityTimelineList .activity-item');
    let visibleCount = 0;

    items.forEach(item => {
        const itemCategory = item.getAttribute('data-category');
        if (category === 'all' || itemCategory === category || (category === 'status' && (itemCategory === 'status' || itemCategory === 'general'))) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptyMsg = document.getElementById('activityFilterEmpty');
    if (emptyMsg) {
        emptyMsg.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
    }
};
</script>
