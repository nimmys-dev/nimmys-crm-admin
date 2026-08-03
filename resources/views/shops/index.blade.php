@extends('layouts.app')

@section('title', 'Shop Management')

@section('page-actions')
    <x-button :href="route('shops.create')" icon="ti ti-plus">Add Shop</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Shops">

                <x-filter-bar
                    :action="route('shops.index')"
                    :search="$filters['q']"
                    :has-active-filters="$hasActiveFilters"
                    placeholder="Name, code, email, phone or city"
                >
                    <x-form.select
                        name="status"
                        label="Status"
                        :options="$statusOptions"
                        :selected="$filters['status']"
                        placeholder="Any status"
                        col="col-span-12 md:col-span-3"
                    />

                    <x-form.select
                        name="city"
                        label="City"
                        :options="$cityOptions"
                        :selected="$filters['city']"
                        placeholder="Any city"
                        col="col-span-12 md:col-span-3"
                    />
                </x-filter-bar>

                <x-datatable
                    :headers="[
                        ['label' => 'Code', 'sort' => 'code'],
                        ['label' => 'Shop', 'sort' => 'name'],
                        'Manager',
                        ['label' => 'City', 'sort' => 'city'],
                        'Staff',
                        ['label' => 'Status', 'sort' => 'status'],
                        ['label' => '', 'class' => 'w-px'],
                    ]"
                    :sort="$filters['sort']"
                    :direction="$filters['direction']"
                    :rows="$shops"
                    :empty-message="$hasActiveFilters
                        ? 'No shops match these filters.'
                        : 'No shops yet. Add the first one to get started.'"
                    empty-icon="ti ti-building-store"
                >
                    @foreach ($shops as $shop)
                                    <tr>
                                        <td><span class="text-muted">{{ $shop->code }}</span></td>

                                        <td>
                                            <a href="{{ route('shops.show', $shop) }}" class="font-medium">{{ $shop->name }}</a>
                                            @if ($shop->email)
                                                <p class="m-0 text-muted text-sm">{{ $shop->email }}</p>
                                            @endif
                                        </td>

                                        <td>{{ $shop->manager?->name ?? '—' }}</td>

                                        <td>{{ $shop->city ?? '—' }}</td>

                                        <td>{{ $shop->staff_count }}</td>

                                        <td><x-status-badge :status="$shop->status->value" /></td>

                                        <td>
                                            <div class="table-actions">
                                                <x-button
                                                    variant="light"
                                                    size="sm"
                                                    :href="route('shops.show', $shop)"
                                                    icon="ti ti-eye"
                                                    aria-label="View {{ $shop->name }}"
                                                />
                                                <x-button
                                                    variant="light"
                                                    size="sm"
                                                    :href="route('shops.edit', $shop)"
                                                    icon="ti ti-pencil"
                                                    aria-label="Edit {{ $shop->name }}"
                                                />
                                                <x-button
                                                    variant="light"
                                                    size="sm"
                                                    icon="ti ti-trash"
                                                    data-pc-toggle="modal"
                                                    data-pc-target="#delete-shop-{{ $shop->id }}"
                                                    aria-label="Delete {{ $shop->name }}"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                    @endforeach
                </x-datatable>

            </x-card>
        </div>
    </div>

    @foreach ($shops as $shop)
        <x-delete-modal
            :id="'delete-shop-' . $shop->id"
            :action="route('shops.destroy', $shop)"
            title="Delete shop"
            :message="'Delete ' . $shop->name . '? Staff and history are kept, and the shop can be restored.'"
        />
    @endforeach

@endsection
