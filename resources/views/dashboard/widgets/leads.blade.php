{{--
    Lead pipeline. Replaces the placeholder that stood here before the Lead
    module existed.

    Figures come from LeadRepository, so they inherit the module's visibility
    scoping: a Manager sees the whole pipeline, an Employee only their own.

    Expects: $leadStats, $dueFollowUps
--}}

<div class="grid grid-cols-12 gap-x-6">

    <div class="col-span-12 xl:col-span-4">
        <x-dashboard-widget
            title="Lead statistics"
            icon="ti ti-target-arrow"
            action-label="View all"
            :action-href="route('leads.index')"
        >
            <div class="placeholder-figures">
                <div>
                    <p class="stat-tile-label">Open</p>
                    <p class="lead-figure">{{ $leadStats['open'] }}</p>
                </div>
                <div>
                    <p class="stat-tile-label">Won</p>
                    <p class="lead-figure text-success-500">{{ $leadStats['won'] }}</p>
                </div>
                <div>
                    <p class="stat-tile-label">Conversion</p>
                    <p class="lead-figure">
                        {{ $leadStats['conversion_rate'] !== null ? $leadStats['conversion_rate'].'%' : '—' }}
                    </p>
                </div>
            </div>

            {{-- Funnel bar: each stage's share of the pipeline, in order. --}}
            @if ($leadStats['total'] > 0)
                <div class="funnel" role="img" aria-label="Leads by stage">
                    @foreach (App\Enums\LeadStatus::cases() as $stage)
                        @php $count = $leadStats['by_status'][$stage->value]; @endphp
                        @if ($count > 0)
                            <span
                                class="funnel-segment {{ $stage->badgeClass() }}"
                                style="flex-grow: {{ $count }}"
                                title="{{ $stage->label() }}: {{ $count }}"
                            >{{ $count }}</span>
                        @endif
                    @endforeach
                </div>
            @else
                <x-empty-state icon="ti ti-target-arrow" message="No leads yet." />
            @endif
        </x-dashboard-widget>
    </div>

    <div class="col-span-12 xl:col-span-8">
        <x-dashboard-widget
            title="Follow-ups due"
            subtitle="Today and overdue"
            icon="ti ti-calendar-event"
        >
            <x-dashboard-table
                :rows="$dueFollowUps"
                :headers="['Lead', 'Owner', 'Due', 'Status']"
                empty-message="Nothing due. Everything is up to date."
                empty-icon="ti ti-calendar-check"
            >
                @foreach ($dueFollowUps as $lead)
                    <tr>
                        <td>
                            <a href="{{ route('leads.show', $lead) }}" class="font-medium">{{ $lead->name }}</a>
                            <p class="m-0 text-muted text-sm">{{ $lead->reference }}</p>
                        </td>

                        <td>{{ $lead->owner?->name ?? '—' }}</td>

                        <td>
                            <span @class(['text-danger-500' => $lead->isOverdue()])>
                                {{ $lead->next_follow_up_at->format('j M Y') }}
                            </span>
                        </td>

                        <td>
                            @if ($lead->isOverdue())
                                <span class="badge badge-lead-lost">Overdue</span>
                            @else
                                <span class="badge badge-due">Today</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-dashboard-table>
        </x-dashboard-widget>
    </div>

</div>
