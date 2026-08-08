{{--
    Salary increments falling due inside the reminder window.

    Shared by both dashboards; the Manager's copy is already scoped to their
    shop by DashboardService, so this partial never filters anything itself.

    Expects: $upcomingIncrements
--}}

<x-dashboard-widget
    title="Upcoming salary increments"
    :subtitle="'Next ' . \App\Models\User::INCREMENT_REMINDER_DAYS . ' days'"
    icon="ti ti-calendar-dollar"
>
    <x-dashboard-table
        :rows="$upcomingIncrements"
        :headers="['Employee', 'Current salary', 'Increment', 'Increment date', 'Days left']"
        empty-message="No increments due in the next {{ \App\Models\User::INCREMENT_REMINDER_DAYS }} days."
        empty-icon="ti ti-calendar-check"
    >
        @foreach ($upcomingIncrements as $employee)
            @php
                // Whole days from today; 0 means the increment lands today.
                $daysLeft = (int) today()->diffInDays($employee->increment_date, false);
            @endphp

            <tr>
                <td>
                    {{-- Managers cannot reach the staff pages, so they get plain text rather than a 403 link. --}}
                    @if ($canManageStaff ?? false)
                        <a href="{{ route('staff.show', $employee) }}" class="font-medium">{{ $employee->name }}</a>
                    @else
                        <span class="font-medium">{{ $employee->name }}</span>
                    @endif

                    <p class="m-0 text-muted text-sm">
                        {{ $employee->employee_code ?? '—' }}@if ($employee->shop) &middot; {{ $employee->shop->name }} @endif
                    </p>
                </td>

                <td class="tabular">
                    {{ filled($employee->salary) ? number_format((float) $employee->salary, 2) : '—' }}
                </td>

                <td class="tabular text-success-500">
                    {{ filled($employee->increment_amount) ? '+'.number_format((float) $employee->increment_amount, 2) : '—' }}
                </td>

                <td>{{ $employee->increment_date->format('d-M-Y') }}</td>

                <td>
                    @if ($daysLeft <= 0)
                        <span class="badge badge-due">Today</span>
                    @elseif ($daysLeft <= 2)
                        <span class="badge badge-due">{{ $daysLeft }} {{ Str::plural('day', $daysLeft) }}</span>
                    @else
                        <span class="badge badge-off">{{ $daysLeft }} days</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-dashboard-table>
</x-dashboard-widget>
