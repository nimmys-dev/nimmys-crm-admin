@extends('layouts.app')

@section('title','Profile')

@section('content')

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h5 class="mb-0">Tasks</h5>
                    <a href="" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Create Task
                    </a>
                </div>
                <div class="card-body">
                    <!-- table -->
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection