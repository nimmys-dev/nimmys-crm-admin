@extends('layouts.app')

@section('title', 'Staff Management')

@section('page-actions')
    <x-button :href="route('staff.create')" icon="ti ti-plus">Add Staff</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Staff">

                <x-filter-bar
                    :action="route('staff.index')"
                    :search="$filters['q']"
                    :has-active-filters="$hasActiveFilters"
                    placeholder="Code, name, email or mobile"
                >
                    <x-form.select
                        name="role"
                        label="Role"
                        :options="$roleOptions"
                        :selected="$filters['role']"
                        placeholder="Any role"
                        col="filter-bar-field"
                        class="form-select-sm"
                    />

                    <x-form.select
                        name="status"
                        label="Status"
                        :options="$statusOptions"
                        :selected="$filters['status']"
                        placeholder="Any status"
                        col="filter-bar-field"
                        class="form-select-sm"
                    />

                    <x-form.select
                        name="shop_id"
                        label="Shop"
                        :options="$shopOptions"
                        :selected="$filters['shop_id']"
                        placeholder="Any shop"
                        col="filter-bar-field"
                        class="form-select-sm"
                    />
                </x-filter-bar>

                <x-datatable
                    :headers="[
                        ['label' => 'Code', 'sort' => 'employee_code'],
                        ['label' => 'Name', 'sort' => 'name'],
                        ['label' => 'Role', 'sort' => 'role'],
                        'Shop',
                        ['label' => 'Increment', 'sort' => 'increment_date'],
                        'Reminder',
                        'Lead access',
                        ['label' => 'Status', 'sort' => 'status'],
                        ['label' => '', 'class' => 'w-px'],
                    ]"
                    :sort="$filters['sort']"
                    :direction="$filters['direction']"
                    :rows="$staff"
                    :empty-message="$hasActiveFilters
                        ? 'No staff match these filters.'
                        : 'No staff yet. Add the first member to get started.'"
                    empty-icon="ti ti-users"
                >
                    @foreach ($staff as $member)
                        <tr>
                            <td>
                                <!-- <span class="text-muted">{{ $member->employee_code ?? '—' }}</span></td> -->
                            <a href="{{ route('staff.show', $member) }}" class="text-primary" style="color:#2171B5">
                                {{ $member->employee_code ?? '—' }}
                            </a>

                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :name="$member->name" :url="$photos->url($member->photo)" />
                                    <div>
                                        <a href="{{ route('staff.show', $member) }}" class="font-medium">{{ $member->name }}</a>
                                        <p class="m-0 text-muted text-sm">{{ $member->email }}</p>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $member->role->label() }}</td>

                            <td>{{ $member->shop?->name ?? '—' }}</td>

                            <td>
                                @if ($member->increment_date)
                                    <span @class(['text-warning-500' => $member->isDueForIncrementReminder()])>
                                        {{ $member->increment_date->format('j M Y') }}
                                    </span>
                                    @if (filled($member->increment_amount))
                                        <p class="m-0 text-muted text-sm">
                                            +{{ number_format((float) $member->increment_amount, 2) }}
                                        </p>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>

                            <td><x-bool-badge :state="$member->increment_notification" on="ON" off="OFF" /></td>

                            <td><x-bool-badge :state="$member->lead_module_access" /></td>

                            <td><x-status-badge :status="$member->status->value" /></td>

                            <td>
                                <div class="table-actions">
                                    <x-button
                                        variant="light"
                                        size="sm"
                                        :href="route('staff.show', $member)"
                                        icon="ti ti-eye"
                                        aria-label="View {{ $member->name }}"
                                    />
                                    <x-button
                                        variant="light"
                                        size="sm"
                                        :href="route('staff.edit', $member)"
                                        icon="ti ti-pencil"
                                        aria-label="Edit {{ $member->name }}"
                                    />
                                    <x-button
                                        variant="light"
                                        size="sm"
                                        icon="ti ti-trash"
                                        data-pc-toggle="modal"
                                        data-pc-target="#delete-staff-{{ $member->id }}"
                                        aria-label="Delete {{ $member->name }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-datatable>

            </x-card>
        </div>
    </div>

    @foreach ($staff as $member)
        <x-delete-modal
            :id="'delete-staff-' . $member->id"
            :action="route('staff.destroy', $member)"
            title="Remove staff member"
            :message="'Remove ' . $member->name . '? Their history is kept and they can no longer sign in.'"
            confirm="Remove"
        />
    @endforeach

@endsection
