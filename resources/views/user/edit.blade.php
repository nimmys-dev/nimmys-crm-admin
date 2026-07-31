@extends('layouts.app')

@section('title','Add User')

@section('content')

<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12">
        <div class="card">
            <div class="card-header">
                <h5>Edit User</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    @csrf
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-6 lg:pr-3">
                            <label class="form-label">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" placeholder="Enter Full Name"mrequired>
                        </div>
                        <div class="col-span-12 md:col-span-6 lg:pr-3">
                            <label class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" class="form-control" placeholder="Enter Email" required>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">
                                Phone Number
                            </label>
                            <input type="text" name="phone" class="form-control" placeholder="Enter Phone Number">
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">
                                Role
                            </label>
                            <select name="role" class="form-select">
                                <option value="">Select Role</option>
                                <option value="Admin">Admin</option>
                                <option value="Manager">Manager</option>
                                <option value="Staff">Staff</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
                        </div>
                        <!-- Confirm Password -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm Password" required>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">
                                Status
                            </label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <!-- Buttons -->
                    <div class="mt-6 flex justify-end gap-3">
                        <a href="" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection