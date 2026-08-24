@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header">
            <h4 class="mb-0">My Tasks</h4>
        </div>

        <div class="card-body">

            {{-- Reassign Bar --}}
            <div class="d-flex justify-content-between align-items-center mb-4">

                {{-- Select All --}}
                <div>
                    <label class="d-flex align-items-center gap-2 mb-0">

                        <input
                            type="checkbox"
                            id="select-all"
                            class="form-check-input"
                        >

                        <span>
                            Select All
                        </span>

                    </label>
                </div>


                {{-- Reassign --}}
                <div class="d-flex gap-2">

                    <select
                        id="reassign_user"
                        class="form-select"
                        style="width: 250px;"
                    >

                        <option value="">
                            Select Role / User
                        </option>

                        @foreach($users->groupBy('role') as $role => $roleUsers)

                            <optgroup
                                label="{{ ucfirst($role) }}"
                            >

                                @foreach($roleUsers as $user)

                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>

                                @endforeach

                            </optgroup>

                        @endforeach

                    </select>


                    <button
                        type="button"
                        id="reassign-tasks"
                        class="btn btn-primary"
                    >
                        <i class="ti ti-user-share"></i>
                        Reassign
                    </button>

                </div>

            </div>


            {{-- Task Table --}}
            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>

                        <tr>

                            <th width="50">
                                <input
                                    type="checkbox"
                                    id="header-select-all"
                                    class="form-check-input"
                                >
                            </th>

                            <th>
                                Task
                            </th>

                            <th>
                                Task Type
                            </th>

                            <th>
                                Assigned To
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($tasks as $task)

                            <tr>

                                <td>

                                    <input
                                        type="checkbox"
                                        class="task-checkbox form-check-input"
                                        value="{{ $task->id }}"
                                    >

                                </td>


                                <td>
                                    {{ $task->title }}
                                </td>


                                <td>
                                    {{ ucfirst($task->task_type) }}
                                </td>


                                <td>
                                    {{ $task->assignedUser?->name ?? '-' }}
                                </td>


                                <td>
                                    {{ ucfirst($task->assignedUser?->role ?? '-') }}
                                </td>


                                <td>
                                    {{ ucfirst($task->status ?? '-') }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6"
                                    class="text-center text-muted">

                                    No tasks found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            <div class="mt-3">

                {{ $tasks->links() }}

            </div>

        </div>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const selectAll =
        document.getElementById('select-all');

    const headerSelectAll =
        document.getElementById('header-select-all');

    const reassignButton =
        document.getElementById('reassign-tasks');

    const reassignUser =
        document.getElementById('reassign_user');


    /*
    |--------------------------------------------------------------------------
    | Select All
    |--------------------------------------------------------------------------
    */

    function toggleAll(checked) {

        document
            .querySelectorAll('.task-checkbox')
            .forEach(function (checkbox) {

                checkbox.checked = checked;

            });

    }


    selectAll.addEventListener('change', function () {

        toggleAll(this.checked);

        headerSelectAll.checked = this.checked;

    });


    headerSelectAll.addEventListener('change', function () {

        toggleAll(this.checked);

        selectAll.checked = this.checked;

    });


    /*
    |--------------------------------------------------------------------------
    | Reassign Tasks
    |--------------------------------------------------------------------------
    */

    reassignButton.addEventListener('click', function () {

        const selectedTasks = [];

        document
            .querySelectorAll('.task-checkbox:checked')
            .forEach(function (checkbox) {

                selectedTasks.push(checkbox.value);

            });


        /*
        | No task selected
        */

        if (selectedTasks.length === 0) {

            alert('Please select at least one task.');

            return;
        }


        /*
        | No user selected
        */

        if (!reassignUser.value) {

            alert('Please select a user.');

            return;
        }


        /*
        | Confirm
        */

        if (!confirm(
            'Are you sure you want to reassign the selected tasks?'
        )) {

            return;
        }


        fetch("{{ route('my-tasks.reassign') }}", {

            method: 'POST',

            headers: {

                'Content-Type': 'application/json',

                'X-CSRF-TOKEN':
                    '{{ csrf_token() }}',

                'Accept':
                    'application/json'

            },

            body: JSON.stringify({

                task_ids: selectedTasks,

                assigned_to:
                    reassignUser.value

            })

        })
        .then(response => response.json())
        .then(data => {

            if (data.success) {

                alert(data.message);

                location.reload();

            } else {

                alert(
                    data.message ||
                    'Something went wrong.'
                );

            }

        })
        .catch(error => {

            console.error(error);

            alert('Something went wrong.');

        });

    });

});
</script>

@endsection