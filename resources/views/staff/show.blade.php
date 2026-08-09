@extends('layouts.app')

@section('title', $staff->name)

@section('page-actions')
    <x-button variant="outline-secondary" :href="route('staff.index')" icon="ti ti-arrow-left">Back</x-button>
    <x-button :href="route('staff.edit', $staff)" icon="ti ti-pencil">Edit</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">

        <div class="col-span-12 xl:col-span-4">
            <x-card>
                <div class="text-center">
                    <x-avatar :name="$staff->name" :url="$photoUrl" size="lg" class="mx-auto" />

                    <h5 class="mt-4 mb-1">{{ $staff->name }}</h5>
                    <p class="text-muted mb-1">{{ $staff->role->label() }}</p>
                    <p class="text-muted mb-3">{{ $staff->employee_code ?? '—' }}</p>

                    <x-status-badge :status="$staff->status->value" />

                    @unless ($staff->role->canAccessWeb())
                        <p class="text-muted text-sm mt-3 mb-0">
                            <i class="ti ti-device-mobile"></i>
                            Mobile access only
                        </p>
                    @endunless
                </div>
            </x-card>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <x-card title="Details">
                <dl class="grid grid-cols-12 gap-4">

                    @php
                        $details = [
                            'Email' => $staff->email,
                            'Mobile' => $staff->phone,
                            'Alternate mobile' => $staff->alternate_phone,
                            'Shop' => $staff->shop?->name,
                            'Joining date' => $staff->joining_date?->format('j M Y'),
                            'Salary' => filled($staff->salary) ? number_format((float) $staff->salary, 2) : null,
                            'Last sign-in' => $staff->last_login_at?->diffForHumans(),
                        ];
                    @endphp

                    @foreach ($details as $label => $value)
                        <div class="col-span-12 md:col-span-6">
                            <dt class="stat-tile-label">{{ $label }}</dt>
                            <dd class="m-0 mt-1">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach

                    @if ($staff->managedShop)
                        <div class="col-span-12 md:col-span-6">
                            <dt class="stat-tile-label">Manages</dt>
                            <dd class="m-0 mt-1">
                                <a href="{{ route('shops.show', $staff->managedShop) }}">{{ $staff->managedShop->name }}</a>
                            </dd>
                        </div>
                    @endif

                    @if ($staff->description)
                        <div class="col-span-12">
                            <dt class="stat-tile-label">Description</dt>
                            <dd class="m-0 mt-1 whitespace-pre-line">{{ $staff->description }}</dd>
                        </div>
                    @endif

                </dl>
            </x-card>

            <x-card title="Salary & access">
                <dl class="grid grid-cols-12 gap-4">

                    <div class="col-span-12 md:col-span-4">
                        <dt class="stat-tile-label">Current salary</dt>
                        <dd class="m-0 mt-1">
                            {{ filled($staff->salary) ? number_format((float) $staff->salary, 2) : '—' }}
                        </dd>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <dt class="stat-tile-label">Increment amount</dt>
                        <dd class="m-0 mt-1">
                            {{ filled($staff->increment_amount) ? '+'.number_format((float) $staff->increment_amount, 2) : '—' }}
                        </dd>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <dt class="stat-tile-label">Salary after increment</dt>
                        <dd class="m-0 mt-1">
                            {{ $staff->projectedSalary() ? number_format((float) $staff->projectedSalary(), 2) : '—' }}
                        </dd>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <dt class="stat-tile-label">Increment date</dt>
                        <dd class="m-0 mt-1">
                            {{ $staff->increment_date?->format('d-M-Y') ?? '—' }}

                            @if ($staff->isDueForIncrementReminder())
                                <span class="badge badge-due">Due soon</span>
                            @endif
                        </dd>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <dt class="stat-tile-label">Increment reminder</dt>
                        <dd class="m-0 mt-1">
                            <x-bool-badge :state="$staff->increment_notification" on="ON" off="OFF" />
                        </dd>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <dt class="stat-tile-label">Lead module access</dt>
                        <dd class="m-0 mt-1">
                            <x-bool-badge :state="$staff->lead_module_access" />
                        </dd>
                    </div>

                </dl>
            </x-card>
        </div>

    </div>

@endsection
