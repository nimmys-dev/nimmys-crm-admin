@extends('layouts.app')

@section('title', 'Lead Management')

@section('page-actions')
    @can('create', App\Models\Lead::class)
        <x-button :href="route('leads.create')" icon="ti ti-plus">Add Lead</x-button>
    @endcan
@endsection

@section('content')

    {{-- Pipeline summary. Already scoped to the viewer by the repository. --}}
    {{-- <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <x-stat-card label="Open leads" :value="$statistics['open']" icon="ti ti-target-arrow" />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <x-stat-card
                label="Due today"
                :value="$statistics['due_today']"
                icon="ti ti-calendar-event"
                :tone="$statistics['due_today'] > 0 ? 'warning' : 'default'"
                :href="route('leads.index', ['due' => 'overdue'])"
            />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <x-stat-card label="Won" :value="$statistics['won']" icon="ti ti-trophy" tone="success" />
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <x-stat-card
                label="Conversion rate"
                :value="$statistics['conversion_rate'] !== null ? $statistics['conversion_rate'] . '%' : null"
                meta="Of decided leads"
                icon="ti ti-chart-arrows"
            />
        </div>
    </div> --}}

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Leads">

                <x-filter-bar
                    :action="route('leads.index')"
                    :search="$filters['q']"
                    :has-active-filters="$hasActiveFilters"
                    placeholder="Reference, name, company, email or phone"
                >
                    {{-- <x-form.select
                        name="status" label="Status" :options="$statusOptions"
                        :selected="$filters['status']" placeholder="Any status"
                        col="filter-bar-field" class="form-select-sm"
                    />

                    <x-form.select
                        name="priority" label="Priority" :options="$priorityOptions"
                        :selected="$filters['priority']" placeholder="Any priority"
                        col="filter-bar-field" class="form-select-sm"
                    />

                    @if ($canAssign)
                        <x-form.select
                            name="assigned_to" label="Owner" :options="$assignableUsers"
                            :selected="$filters['assigned_to']" placeholder="Anyone"
                            col="filter-bar-field" class="form-select-sm"
                        />
                    @endif --}}
                </x-filter-bar>

                <x-datatable
                    :headers="[
                        ['label' => 'Lead', 'sort' => 'name'],
                        'Phone',
                        'Assigned To',
                        ['label' => 'Status', 'sort' => 'status'],
                        'Next follow-up',
                        'Remarks',
                        ['label' => 'Created', 'sort' => 'created_at'],
                        ['label' => '', 'class' => 'w-px'],
                    ]"
                    :sort="$filters['sort']"
                    :direction="$filters['direction']"
                    :rows="$leads"
                    :empty-message="$hasActiveFilters
                        ? 'No leads match these filters.'
                        : 'No leads yet. Add the first one to get started.'"
                    empty-icon="ti ti-target-arrow"
                >
                    @foreach ($leads as $lead)
                        <tr>
                            <td>
                                <a href="{{ route('leads.show', $lead) }}" class="font-medium">{{ $lead->name }}</a>
                                @if ($lead->company)
                                    <p class="m-0 text-muted text-sm">{{ $lead->company }}</p>
                                @endif
                            </td>

                            <td>{{ $lead->phone }}</td>

                            <td>{{ $lead->owner?->name ?? '—' }}</td>

                            <td>
                                <x-bool-badge :state="$lead->status->isOpen()" on="Open" off="Closed" />
                            </td>

                            <td>
                                {{-- Sourced from the latest logged call, not the lead's own
                                     next_follow_up_at — that column tracks the separate,
                                     currently-unused follow-up feature. --}}
                                @php $nextFollowUp = $lead->latestCall?->next_followup_date; @endphp

                                @if ($nextFollowUp)
                                    @php $isOverdue = $lead->status->isOpen() && $nextFollowUp->isPast(); @endphp
                                    <span @class(['text-danger-500' => $isOverdue])>
                                        {{ $nextFollowUp->format('j M Y') }}
                                    </span>
                                    @if ($isOverdue)
                                        <span class="badge badge-lead-lost">Overdue</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                {{ $lead->description}}
                            </td>
                            <td>{{ $lead->created_at->format('j M Y') }}</td>
                            

                            <td>
                                <div class="table-actions">
                                    <x-button
                                        variant="light" size="sm"
                                        :href="route('leads.show', $lead)"
                                        icon="ti ti-eye"
                                        aria-label="View {{ $lead->reference }}"
                                    />

                                    @can('update', $lead)
                                        <x-button
                                            variant="light" size="sm"
                                            :href="route('leads.edit', $lead)"
                                            icon="ti ti-pencil"
                                            aria-label="Edit {{ $lead->reference }}"
                                        />
                                    @endcan

                                    <!-- @can('delete', $lead)
                                        <x-button
                                            variant="light" size="sm"
                                            icon="ti ti-trash"
                                            data-pc-toggle="modal"
                                            data-pc-target="#delete-lead-{{ $lead->id }}"
                                            aria-label="Delete {{ $lead->reference }}"
                                        />
                                    @endcan -->
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-datatable>

            </x-card>
        </div>
    </div>

    @foreach ($leads as $lead)
        @can('delete', $lead)
            <x-delete-modal
                :id="'delete-lead-' . $lead->id"
                :action="route('leads.destroy', $lead)"
                title="Delete lead"
                :message="'Delete ' . $lead->reference . ' (' . $lead->name . ')? Follow-up history is kept and the lead can be restored.'"
            />
        @endcan
    @endforeach

@endsection
