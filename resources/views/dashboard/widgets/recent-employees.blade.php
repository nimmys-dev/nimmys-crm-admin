{{--
    Most recently added staff.

    Expects: $recentEmployees, $photos (StaffPhotoService)
    Optional: $canManageStaff — hides the "View all" link for roles that
    cannot reach the staff index.
--}}

<x-dashboard-widget
    title="Recent employees"
    icon="ti ti-user-plus"
    :action-label="($canManageStaff ?? false) ? 'View all' : null"
    :action-href="($canManageStaff ?? false) ? route('staff.index') : null"
>
    <x-dashboard-table
        :rows="$recentEmployees"
        :headers="[
            ['label' => '', 'class' => 'w-px'],
            'Employee',
            'Role',
            'Shop',
            'Joined',
        ]"
        empty-message="No staff added yet."
        empty-icon="ti ti-users"
    >
        @foreach ($recentEmployees as $employee)
            <tr>
                <td>
                    <x-avatar :name="$employee->name" :url="$photos->url($employee->photo)" />
                </td>

                <td>
                    @if ($canManageStaff ?? false)
                        <a href="{{ route('staff.show', $employee) }}" class="font-medium">{{ $employee->name }}</a>
                    @else
                        <span class="font-medium">{{ $employee->name }}</span>
                    @endif
                    <p class="m-0 text-muted text-sm">{{ $employee->employee_code ?? '—' }}</p>
                </td>

                <td>{{ $employee->role->label() }}</td>

                <td>{{ $employee->shop?->name ?? '—' }}</td>

                <td>{{ $employee->joining_date?->format('j M Y') ?? '—' }}</td>
            </tr>
        @endforeach
    </x-dashboard-table>
</x-dashboard-widget>
