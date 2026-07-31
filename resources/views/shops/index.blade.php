@extends('layouts.app')

@section('title', 'Shop Management')

@section('page-actions')
    <x-button icon="ti ti-plus" disabled>Add Shop</x-button>
@endsection

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card title="Shops">
                <x-datatable
                    :headers="['Name', 'Owner', 'Location', 'Status', '']"
                    empty-message="No shops yet. The shop module is not connected."
                    empty-icon="ti ti-building-store"
                />
            </x-card>
        </div>
    </div>

@endsection
