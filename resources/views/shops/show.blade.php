@extends('layouts.app')

@section('title', $shop->name)

@section('page-actions')
    <x-button variant="outline-secondary" :href="route('shops.index')" icon="ti ti-arrow-left">Back</x-button>
    <x-button :href="route('shops.edit', $shop)" icon="ti ti-pencil">Edit</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">

        <div class="col-span-12 xl:col-span-8">
            <x-card title="Shop details">
                <dl class="grid grid-cols-12 gap-4">

                    @php
                        $details = [
                            'Shop code' => $shop->code,
                            'Name' => $shop->name,
                            'Manager' => $shop->manager?->name,
                            'Email' => $shop->email,
                            'Phone' => $shop->phone,
                            'Opened' => $shop->opened_on?->format('j M Y'),
                            'Address' => $shop->fullAddress() ?: null,
                        ];
                    @endphp

                    @foreach ($details as $label => $value)
                        <div class="col-span-12 md:col-span-6">
                            <dt class="stat-tile-label">{{ $label }}</dt>
                            <dd class="m-0 mt-1">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach

                    <div class="col-span-12 md:col-span-6">
                        <dt class="stat-tile-label">Status</dt>
                        <dd class="m-0 mt-1"><x-status-badge :status="$shop->status->value" /></dd>
                    </div>

                    @if ($shop->notes)
                        <div class="col-span-12">
                            <dt class="stat-tile-label">Notes</dt>
                            <dd class="m-0 mt-1 whitespace-pre-line">{{ $shop->notes }}</dd>
                        </div>
                    @endif

                </dl>
            </x-card>
        </div>

        <div class="col-span-12 xl:col-span-4">
            <x-card :title="'Staff (' . $shop->staff->count() . ')'">
                <x-datatable
                    :headers="['Name', 'Role', 'Status']"
                    empty-message="No staff assigned to this shop."
                    empty-icon="ti ti-users"
                >
                    @foreach ($shop->staff as $member)
                        <tr>
                            <td>
                                {{ $member->name }}
                                <p class="m-0 text-muted text-sm">{{ $member->email }}</p>
                            </td>
                            <td>{{ $member->role->label() }}</td>
                            <td><x-status-badge :status="$member->status->value" /></td>
                        </tr>
                    @endforeach
                </x-datatable>
            </x-card>
        </div>

    </div>

@endsection
