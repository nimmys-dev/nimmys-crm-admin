@extends('layouts.app')

@section('title', 'Search')

@section('content')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <x-card :title="filled($term) ? 'Results for “' . $term . '”' : 'Search'">
                <x-datatable
                    :headers="['Module', 'Match', 'Updated']"
                    :empty-message="filled($term)
                        ? 'Nothing matched. Search is not connected to any module yet.'
                        : 'Enter a term in the header search box.'"
                    empty-icon="ti ti-search"
                />
            </x-card>
        </div>
    </div>

@endsection
