@extends('layouts.app')

@section('title','Profile')

@section('content')

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5>User Profile</h5>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-6">
                        <!-- Profile Image -->
                        <div class="col-span-12 lg:col-span-4">
                            <div class="text-center">
                                <img src="{{ asset('assets/images/user/avatar-1.jpg') }}"
                                    class="w-32 h-32 rounded-full mx-auto border-4 border-gray-200"
                                    alt="Profile">
                                <h4 class="mt-4 font-semibold">Test</h4>
                                <p class="text-gray-500">
                                    test@gmail.com
                                </p>
                                <span
                                    class="inline-block px-3 py-1 mt-2 text-sm bg-green-100 text-green-700 rounded-full">
                                    Active
                                </span>

                            </div>
                        </div>
                        <!-- Profile Details -->
                        <div class="col-span-12 lg:col-span-8">
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-6">
                                    <label class="font-medium">Full Name</label>
                                    <input type="text" class="form-control mt-2" value="" readonly>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="font-medium">Email</label>
                                    <input type="email" class="form-control mt-2" value="test@gmail.com" readonly>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="font-medium">Phone</label>
                                    <input type="text" class="form-control mt-2" value="+91 9876543210" readonly>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="font-medium">Role</label>
                                    <input type="text" class="form-control mt-2" value="Administrator" readonly>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="font-medium">Joined Date</label>
                                    <input type="text" class="form-control mt-2" value="" readonly>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="font-medium">Status</label>
                                    <input type="text" class="form-control mt-2" value="Active" readonly>
                                </div>
                            </div>
                            <div class="mt-6 flex gap-3">
                                <a href="" class="btn btn-primary flex-1 py-3 text-center text-sm whitespace-nowrap">
                                    Edit Profile
                                </a>
                                <a href="" class="btn btn-outline-secondary flex-1 py-3 text-center text-sm whitespace-nowrap">
                                    Change Password
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection