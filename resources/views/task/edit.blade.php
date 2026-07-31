@extends('layouts.app')

@section('title','Create Task')

@section('content')

<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12">
        <div class="card">
            <div class="card-header">
                <h5>Edit Task</h5>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    @csrf
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-6 lg:pr-3">
                            <label class="form-label">
                                Task Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" class="form-control" placeholder="Enter Task Title"required>
                        </div>
                        <div class="col-span-12 md:col-span-6 lg:pl-3">
                            <label class="form-label">
                                Assign To <span class="text-danger">*</span>
                            </label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select User</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-6 lg:pr-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-6 lg:pl-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-span-12 md:col-span-6 lg:pr-3">
                            <label class="form-label">
                                Start Date
                            </label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-span-12 md:col-span-6 lg:pl-3">
                            <label class="form-label">
                                Due Date
                            </label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-span-12">
                            <label class="form-label">
                                Task Description
                            </label>
                            <textarea name="description" rows="5" class="form-control" placeholder="Enter Task Description"></textarea>
                        </div>

                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href=""  class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Save Task
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection