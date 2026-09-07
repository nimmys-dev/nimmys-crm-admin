<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->get('report_type');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $search = $request->get('search');
        $perPage = 10; // Page-ൽ കാണിക്കേണ്ട റെക്കോർഡുകളുടെ എണ്ണം

        // 1. LEAD REPORTS DATA
        if ($reportType === 'lead') {
            $query = Lead::with('assignedUser');

            if ($fromDate) $query->whereDate('created_at', '>=', $fromDate);
            if ($toDate) $query->whereDate('created_at', '<=', $toDate);
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $reports = $query->latest()->paginate($perPage)->through(function ($lead) {
                return [
                    'id'           => $lead->id,
                    'title'        => $lead->name . ($lead->company ? ' (' . $lead->company . ')' : ''),
                    'type'         => 'lead',
                    'status'       => is_object($lead->status) ? ($lead->status->value ?? $lead->status->name) : $lead->status,
                    'period'       => $lead->created_at ? $lead->created_at->format('Y-m-d') : 'N/A',
                    'generated_by' => $lead->assignedUser->name ?? 'Unassigned',
                ];
            });

        // 2. TASK REPORTS DATA
        } elseif ($reportType === 'task') {
            $query = Task::with('assignedUser');

            if ($fromDate) $query->whereDate('created_at', '>=', $fromDate);
            if ($toDate) $query->whereDate('created_at', '<=', $toDate);
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%");
                });
            }

            $reports = $query->latest()->paginate($perPage)->through(function ($task) {
                return [
                    'id'           => $task->id,
                    'title'        => $task->title,
                    'type'         => 'task',
                    'status'       => is_object($task->status) ? ($task->status->value ?? $task->status->name) : $task->status,
                    'period'       => $task->created_at ? $task->created_at->format('Y-m-d') : 'N/A',
                    'generated_by' => $task->assignedUser->name ?? 'Unassigned',
                ];
            });

        // 3. STAFF PERFORMANCE DATA
        } elseif ($reportType === 'staff') {
            $query = User::query();

            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }

            $reports = $query->paginate($perPage)->through(function ($user) use ($fromDate, $toDate) {
                $totalLeadsQuery = Lead::where('assigned_to', $user->id);
                $closedLeadsQuery = Lead::where('assigned_to', $user->id)->where('status', 'closed');
                $completedTasksQuery = Task::where('assigned_to', $user->id)->where('status', 'completed');

                if ($fromDate) {
                    $totalLeadsQuery->whereDate('created_at', '>=', $fromDate);
                    $closedLeadsQuery->whereDate('created_at', '>=', $fromDate);
                    $completedTasksQuery->whereDate('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $totalLeadsQuery->whereDate('created_at', '<=', $toDate);
                    $closedLeadsQuery->whereDate('created_at', '<=', $toDate);
                    $completedTasksQuery->whereDate('created_at', '<=', $toDate);
                }

                return [
                    'id'              => $user->id,
                    'staff_name'      => $user->name,
                    'total_leads'     => $totalLeadsQuery->count(),
                    'closed_leads'    => $closedLeadsQuery->count(),
                    'completed_tasks' => $completedTasksQuery->count(),
                    'period'          => now()->format('Y-m-d'),
                ];
            });

        // 4. ALL REPORTS COMBINED (Merged Pagination)
        } else {
            $tasksQuery = Task::with('assignedUser');
            $leadsQuery = Lead::with('assignedUser');

            if ($fromDate) {
                $tasksQuery->whereDate('created_at', '>=', $fromDate);
                $leadsQuery->whereDate('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $tasksQuery->whereDate('created_at', '<=', $toDate);
                $leadsQuery->whereDate('created_at', '<=', $toDate);
            }

            $taskList = $tasksQuery->latest()->get()->map(function ($task) {
                return [
                    'id'           => $task->id,
                    'title'        => $task->title,
                    'type'         => 'task',
                    'status'       => is_object($task->status) ? ($task->status->value ?? $task->status->name) : $task->status,
                    'period'       => $task->created_at ? $task->created_at->format('Y-m-d') : 'N/A',
                    'generated_by' => $task->assignedUser->name ?? 'Unassigned',
                    'created_at'   => $task->created_at,
                ];
            });

            $leadList = $leadsQuery->latest()->get()->map(function ($lead) {
                return [
                    'id'           => $lead->id,
                    'title'        => $lead->name,
                    'type'         => 'lead',
                    'status'       => is_object($lead->status) ? ($lead->status->value ?? $lead->status->name) : $lead->status,
                    'period'       => $lead->created_at ? $lead->created_at->format('Y-m-d') : 'N/A',
                    'generated_by' => $lead->assignedUser->name ?? 'Unassigned',
                    'created_at'   => $lead->created_at,
                ];
            });

            $combined = $taskList->concat($leadList)->sortByDesc('created_at');

            if ($search) {
                $searchLower = strtolower($search);
                $combined = $combined->filter(function ($item) use ($searchLower) {
                    return str_contains(strtolower($item['title']), $searchLower)
                        || str_contains(strtolower($item['generated_by']), $searchLower)
                        || str_contains(strtolower($item['status']), $searchLower);
                });
            }

            // Manual Collection Pagination for All Reports
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $currentPageItems = $combined->slice(($currentPage - 1) * $perPage, $perPage)->values();
            
            $reports = new LengthAwarePaginator(
                $currentPageItems,
                $combined->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        // Export Actions
        if ($request->has('export')) {
            return $this->exportData($reports, $request->get('export'), $reportType);
        }

        return view('reports.index', compact('reports', 'reportType'));
    }

    /**
     * CSV/Excel ഡൗൺലോഡ് ചെയ്യുന്നതിനുള്ള Export Functionality
     */
    private function exportData($data, $format, $type)
    {
        $filename = "Reports_Export_" . date('Y-m-d') . ".xls";

        // Dynamic Report Sub-Title
        $reportTitle = match($type) {
            'task'  => 'TASK PERFORMANCE REPORT',
            'lead'  => 'LEAD PERFORMANCE REPORT',
            'staff' => 'STAFF PERFORMANCE REPORT',
            default => 'ALL REPORTS SUMMARY',
        };

        $colSpan = 5; // 5 Columns ഉള്ളതുകൊണ്ട് colspan=5 നൽകുന്നു

        return response()->streamDownload(function () use ($data, $type, $reportTitle, $colSpan) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            
            // Excel-ൽ ശൂന്യമായ ഭാഗത്ത് Native Gridlines കാണാനുള്ള XML
            echo '<!--[if gte mso 9]><xml>';
            echo ' <x:ExcelWorkbook>';
            echo '  <x:ExcelWorksheets>';
            echo '   <x:ExcelWorksheet>';
            echo '    <x:Name>Report</x:Name>';
            echo '    <x:WorksheetOptions>';
            echo '     <x:DisplayGridlines/>';
            echo '    </x:WorksheetOptions>';
            echo '   </x:ExcelWorksheet>';
            echo '  </x:ExcelWorksheets>';
            echo ' </x:ExcelWorkbook>';
            echo '</xml><![endif]-->';

            echo '<style>';
            echo '  body { font-family: "Segoe UI", Arial, sans-serif; }';
            echo '  table { border-collapse: collapse; }';
            echo '  th { background-color: #7A0C16; color: #ffffff; font-weight: bold; padding: 10px; border: 1px solid #000000; text-align: center; font-size: 14px; }';
            echo '  td { padding: 8px 12px; border: 1px solid #D1D5DB; font-size: 13px; color: #111827; text-align: center; }';
            
            // ഹെഡിംഗുകൾ സെന്ററിലാക്കാൻ ഉള്ള സ്റ്റൈൽ
            echo '  .company-header { font-size: 22px; font-weight: bold; color: #7A0C16; text-align: center !important; border: none !important; }';
            echo '  .report-subheading { font-size: 15px; font-weight: bold; color: #1F2937; text-align: center !important; border: none !important; }';
            echo '  .meta-info { font-size: 12px; color: #4B5563; text-align: center !important; border: none !important; }';
            
            echo '  .text-left { text-align: left !important; }';
            echo '  .status-completed, .status-approved { background-color: #D1FAE5; color: #065F46; font-weight: bold; }';
            echo '  .status-overdue { background-color: #FEE2E2; color: #991B1B; font-weight: bold; }';
            echo '  .status-default { background-color: #F3F4F6; color: #374151; font-weight: bold; }';
            echo '  .type-badge { font-weight: bold; text-transform: uppercase; color: #4F46E5; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<table>';

            // All Headings Centered via Colspan
            echo '<tr><td colspan="' . $colSpan . '" class="company-header" style="padding-top: 10px;">NIMMY\'S CAMERAS</td></tr>';
            echo '<tr><td colspan="' . $colSpan . '" class="report-subheading">' . $reportTitle . '</td></tr>';
            echo '<tr><td colspan="' . $colSpan . '" class="meta-info" style="padding-bottom: 10px;">Generated Date: ' . date('Y-m-d H:i') . ' | Total Records: ' . count($data) . '</td></tr>';
            
            // Space Row (Merge വരാതിരിക്കാൻ)
            echo '<tr>';
            for ($i = 0; $i < $colSpan; $i++) {
                echo '<style></style><td style="border: none;"></td>';
            }
            echo '</tr>';

            // Table Data Section
            if ($type === 'staff') {
                echo '<thead>';
                echo '  <tr>';
                echo '      <th>Staff Name</th>';
                echo '      <th>Total Leads Assigned</th>';
                echo '      <th>Closed Leads</th>';
                echo '      <th>Completed Tasks</th>';
                echo '      <th>Date</th>';
                echo '  </tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($data as $row) {
                    echo '<tr>';
                    echo '  <td class="text-left" style="font-weight: 600;">' . htmlspecialchars($row['staff_name']) . '</td>';
                    echo '  <td>' . htmlspecialchars($row['total_leads']) . '</td>';
                    echo '  <td>' . htmlspecialchars($row['closed_leads']) . '</td>';
                    echo '  <td>' . htmlspecialchars($row['completed_tasks']) . '</td>';
                    echo '  <td>' . htmlspecialchars($row['period']) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<thead>';
                echo '  <tr>';
                echo '      <th>Title</th>';
                echo '      <th>Type</th>';
                echo '      <th>Status</th>';
                echo '      <th>Period / Date</th>';
                echo '      <th>Assigned Staff</th>';
                echo '  </tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($data as $row) {
                    $status = strtolower($row['status'] ?? '');
                    $statusClass = match(true) {
                        str_contains($status, 'completed'), str_contains($status, 'approved') => 'status-completed',
                        str_contains($status, 'overdue') => 'status-overdue',
                        default => 'status-default',
                    };

                    echo '<tr>';
                    echo '  <td class="text-left" style="font-weight: 600;">' . htmlspecialchars($row['title']) . '</td>';
                    echo '  <td class="type-badge">' . htmlspecialchars(strtoupper($row['type'] ?? 'N/A')) . '</td>';
                    echo '  <td class="' . $statusClass . '">' . htmlspecialchars(ucfirst($row['status'] ?? 'N/A')) . '</td>';
                    echo '  <td>' . htmlspecialchars($row['period']) . '</td>';
                    echo '  <td class="text-left">' . htmlspecialchars($row['generated_by']) . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, $filename, [
            "Content-Type" => "application/vnd.ms-excel; charset=utf-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Download Single Report Details as Excel File
     */
    public function download($id)
    {
        $task = \App\Models\Task::with(['assignedTo'])->find($id);

        if ($task) {
            $reportData = [
                'id'           => $task->id,
                'title'        => $task->title ?? 'N/A',
                'type'         => 'Task',
                'status'       => is_object($task->status) ? ($task->status->value ?? $task->status->name) : ($task->status ?? 'N/A'),
                'period'       => $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : ($task->created_at ? $task->created_at->format('Y-m-d') : 'N/A'),
                'generated_by' => $task->assignedTo->name ?? 'N/A',
                'description'  => $task->description ?? 'N/A',
            ];
        } else {
            $lead = \App\Models\Lead::with('assignedTo')->find($id);

            if ($lead) {
                $reportData = [
                    'id'           => $lead->id,
                    'title'        => $lead->title ?? $lead->name ?? 'N/A',
                    'type'         => 'Lead',
                    'status'       => is_object($lead->status) ? ($lead->status->value ?? $lead->status->name) : ($lead->status ?? 'N/A'),
                    'period'       => $lead->created_at ? $lead->created_at->format('Y-m-d') : 'N/A',
                    'generated_by' => $lead->assignedTo->name ?? 'N/A',
                    'description'  => $lead->notes ?? 'N/A',
                ];
            } else {
                return back()->with('error', 'Report record not found.');
            }
        }

        $filename = "Report_#{$id}_" . date('Y-m-d') . ".xls";

        return response()->streamDownload(function () use ($reportData) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            
            echo '<!--[if gte mso 9]><xml>';
            echo ' <x:ExcelWorkbook>';
            echo '  <x:ExcelWorksheets>';
            echo '   <x:ExcelWorksheet>';
            echo '    <x:Name>Single Report</x:Name>';
            echo '    <x:WorksheetOptions>';
            echo '     <x:DisplayGridlines/>';
            echo '    </x:WorksheetOptions>';
            echo '   </x:ExcelWorksheet>';
            echo '  </x:ExcelWorksheets>';
            echo ' </x:ExcelWorkbook>';
            echo '</xml><![endif]-->';

            echo '<style>';
            echo '  body { font-family: "Segoe UI", Arial, sans-serif; }';
            echo '  table { border-collapse: collapse; }';
            echo '  th { background-color: #7A0C16; color: #ffffff; font-weight: bold; padding: 10px; border: 1px solid #000000; text-align: left; font-size: 14px; }';
            echo '  td { padding: 8px 12px; border: 1px solid #D1D5DB; font-size: 13px; color: #111827; text-align: left; }';
            
            echo '  .company-header { font-size: 20px; font-weight: bold; color: #7A0C16; text-align: center !important; border: none !important; }';
            echo '  .report-subheading { font-size: 14px; font-weight: bold; color: #1F2937; text-align: center !important; border: none !important; }';
            echo '  .meta-info { font-size: 12px; color: #4B5563; text-align: center !important; border: none !important; }';
            
            echo '  .status-completed, .status-approved { background-color: #D1FAE5; color: #065F46; font-weight: bold; }';
            echo '  .status-overdue { background-color: #FEE2E2; color: #991B1B; font-weight: bold; }';
            echo '  .status-default { background-color: #F3F4F6; color: #374151; font-weight: bold; }';
            echo '</style>';
            echo '</head>';
            echo '<body>';

            echo '<table>';

            // Single Report Header Centered
            echo '<tr><td colspan="2" class="company-header">NIMMY\'S CAMERAS</td></tr>';
            echo '<tr><td colspan="2" class="report-subheading">SINGLE REPORT DETAILS (#' . $reportData['id'] . ')</td></tr>';
            echo '<tr><td colspan="2" class="meta-info">Downloaded Date: ' . date('Y-m-d H:i') . '</td></tr>';
            
            echo '<tr><td style="border: none;"></td><td style="border: none;"></td></tr>';

            // Details Table
            echo '<tr><th>Report ID</th><td>#' . htmlspecialchars($reportData['id']) . '</td></tr>';
            echo '<tr><th>Title</th><td style="font-weight: bold;">' . htmlspecialchars($reportData['title']) . '</td></tr>';
            echo '<tr><th>Type</th><td style="text-transform: uppercase; font-weight: bold; color: #4F46E5;">' . htmlspecialchars($reportData['type']) . '</td></tr>';
            
            $status = strtolower($reportData['status']);
            $statusClass = match(true) {
                str_contains($status, 'completed'), str_contains($status, 'approved') => 'status-completed',
                str_contains($status, 'overdue') => 'status-overdue',
                default => 'status-default',
            };

            echo '<tr><th>Status</th><td class="' . $statusClass . '">' . htmlspecialchars(ucfirst($reportData['status'])) . '</td></tr>';
            echo '<tr><th>Date / Period</th><td>' . htmlspecialchars($reportData['period']) . '</td></tr>';
            echo '<tr><th>Assigned Staff</th><td>' . htmlspecialchars($reportData['generated_by']) . '</td></tr>';
            echo '<tr><th>Description / Details</th><td>' . htmlspecialchars($reportData['description']) . '</td></tr>';

            echo '</table>';
            echo '</body>';
            echo '</html>';

        }, $filename, [
            "Content-Type" => "application/vnd.ms-excel; charset=utf-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
        ]);
    }
}