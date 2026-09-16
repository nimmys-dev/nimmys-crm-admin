<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
        }

        th {
            background-color: #1f4e78;
            color: #ffffff;
            font-weight: bold;
            border: 1px solid #000000;
            padding: 10px;
            text-align: center;
        }

        td {
            border: 1px solid #cccccc;
            padding: 8px;
        }

        .center {
            text-align: center;
        }

        .date {
            white-space: nowrap;
        }
    </style>
</head>

<body>

<table>
    <thead>
        <tr>
            <th>S.No</th>
            <th>Task Name</th>
            <th>Assigned Staff</th>
            <th>Period</th>
            <th>Repeat Mode</th>
            <th>Status</th>
            <th>Approved By</th>
            <th>Created Date</th>
        </tr>
    </thead>

    <tbody>

        @forelse($tasks as $index => $task)

            <tr>
                <td class="center">
                    {{ $index + 1 }}
                </td>

                <td>
                    {{ $task->title ?? '-' }}
                </td>

                <td>
                    {{ $task->assignedTo?->name ?? '-' }}
                </td>

                <td class="center">
                    {{ ucfirst($task->task_type ?? '-') }}
                </td>

                <td class="center">
                    {{ $task->repeat_mode ?? '-' }}
                </td>

                <td class="center">
                    {{ ucfirst(str_replace('_', ' ', $task->status ?? '-')) }}
                </td>

                <td>
                    {{ $task->approvedBy?->name ?? '-' }}
                </td>

                <td class="center date">
                    {{ $task->created_at?->format('d-m-Y') ?? '-' }}
                </td>
            </tr>

        @empty

            <tr>
                <td colspan="8" style="text-align: center;">
                    No tasks found
                </td>
            </tr>

        @endforelse

    </tbody>
</table>

</body>
</html>