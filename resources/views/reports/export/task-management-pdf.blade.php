<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Task Management Report</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0 0 5px 0;
        }

        .header p {
            margin: 0;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #1f4e78;
            color: #fff;
            padding: 8px;
            border: 1px solid #333;
            text-align: left;
        }

        td {
            padding: 7px;
            border: 1px solid #ccc;
        }

        .center {
            text-align: center;
        }

        .no-print {
            margin-bottom: 20px;
        }

        .print-btn {
            padding: 9px 18px;
            border: 0;
            background: #198754;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="no-print">
    <button class="print-btn" onclick="window.print()">
        Print / Save as PDF
    </button>
</div>

<div class="header">
    <h2>Task Management Report</h2>
    <p>
        Generated on:
        {{ now()->format('d-m-Y H:i') }}
    </p>
</div>

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

                <td class="center">
                    {{ $task->created_at?->format('d-m-Y') ?? '-' }}
                </td>

            </tr>

        @empty

            <tr>
                <td colspan="8" class="center">
                    No tasks found
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

<script>
    window.onload = function () {
        window.print();
    };
</script>

</body>
</html>