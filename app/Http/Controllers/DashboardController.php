<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use App\Services\StaffPhotoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\LeadActivity;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly StaffPhotoService $photos,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard($user);
        }

        if ($user->isManager()) {
            return $this->managerDashboard($user);
        }

        return $this->employeeDashboard($user);
    }

    private function employeeDashboard(User $user): View
    {
        $cacheKey = "employee_dashboard_{$user->id}";

        $dashboardData = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($user) {
            return [
                'taskCounts'       => $this->getTaskDashboardCounts($user),
                'leadStats'        => $this->dashboard->getDashboardLeadStatistics($user),
                'statistics'       => $this->dashboard->getDashboardAllLeadStatistics($user),
                'recentActivities' => LeadActivity::latest()->take(5)->get(),
            ];
        });

        return view('dashboard.employee', [
            'pageTitle'        => 'Dashboard',
            'breadcrumbs'      => [['label' => 'Dashboard']],
            'user'             => $user,
            'shop'             => $user->shop,
            'leadStats'        => $dashboardData['leadStats'],
            'todayDuty'        => $dashboardData['taskCounts']['todayDuty'] ?? 0,
            'overdueDuty'      => $dashboardData['taskCounts']['overdueDuty'] ?? 0,
            'upcomingDuty'     => $dashboardData['taskCounts']['upcomingDuty'] ?? 0,
            'approvalPending'  => $dashboardData['taskCounts']['approvalPending'] ?? 0,
            'sendingApproval'  => $dashboardData['taskCounts']['sendingApproval'] ?? 0,
            'recentActivities' => $dashboardData['recentActivities'],
            'statistics'       => $dashboardData['statistics'],
        ]);
    }

    private function adminDashboard(User $user): View
    {
        $cacheKey = "admin_dashboard_{$user->id}";

        $dashboardData = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($user) {
            return [
                'taskCounts'       => $this->getTaskDashboardCounts($user),
                'adminTaskCounts'  => $this->getAdminAssignedTaskCounts($user),
                'adminStats'       => $this->dashboard->getAdminStatistics(),
                'upcomingIncrements' => $this->dashboard->getUpcomingIncrements(),
                'recentEmployees'  => $this->dashboard->getRecentEmployees(),
                'recentShops'      => $this->dashboard->getRecentShops(),
                'leadStats'        => $this->dashboard->getDashboardLeadStatistics($user),
                'statistics'       => $this->dashboard->getDashboardAllLeadStatistics($user),
                'dueFollowUps'     => $this->dashboard->getDueFollowUps($user),
                'recentActivities' => LeadActivity::latest()->take(5)->get(),
            ];
        });

        return view('dashboard.admin', [
            'pageTitle'                    => 'Dashboard',
            'breadcrumbs'                  => [['label' => 'Dashboard']],
            'photos'                       => $this->photos,
            'stats'                        => $dashboardData['adminStats'],
            'upcomingIncrements'           => $dashboardData['upcomingIncrements'],
            'recentEmployees'              => $dashboardData['recentEmployees'],
            'recentShops'                  => $dashboardData['recentShops'],
            'leadStats'                    => $dashboardData['leadStats'],
            'statistics'                   => $dashboardData['statistics'],
            'dueFollowUps'                 => $dashboardData['dueFollowUps'],
            'todayDuty'                    => $dashboardData['taskCounts']['todayDuty'] ?? 0,
            'overdueDuty'                  => $dashboardData['taskCounts']['overdueDuty'] ?? 0,
            'upcomingDuty'                 => $dashboardData['taskCounts']['upcomingDuty'] ?? 0,
            'approvalPending'              => $dashboardData['taskCounts']['approvalPending'] ?? 0,
            'sendingApproval'              => $dashboardData['taskCounts']['sendingApproval'] ?? 0,
            'adminAssignedTaskCount'       => $dashboardData['adminTaskCounts']['total'] ?? 0,
            'adminOngoingTaskCount'        => $dashboardData['adminTaskCounts']['ongoing'] ?? 0,
            'adminOverdueTaskCount'        => $dashboardData['adminTaskCounts']['overdue'] ?? 0,
            'adminUpcomingTaskCount'       => $dashboardData['adminTaskCounts']['upcoming'] ?? 0,
            'adminApprovalPendingTaskCount' => $dashboardData['adminTaskCounts']['approval_pending'] ?? 0,
            'adminSendingPendingTaskCount' => $dashboardData['adminTaskCounts']['sending_approval'] ?? 0,
            'recentActivities'             => $dashboardData['recentActivities'],
        ]);
    }

    private function managerDashboard(User $user): View
    {
        $cacheKey = "manager_dashboard_{$user->id}";

        $dashboardData = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($user) {
            $stats = $this->dashboard->getManagerStatistics($user);
            $shopId = $stats['shop']?->id;

            return [
                'stats'              => $stats,
                'upcomingIncrements' => $shopId ? $this->dashboard->getUpcomingIncrements($shopId) : collect(),
                'recentEmployees'    => $shopId ? $this->dashboard->getRecentEmployees($shopId) : collect(),
                'taskCounts'         => $this->getTaskDashboardCounts($user),
                'adminTaskCounts'    => $this->getAdminAssignedTaskCounts($user),
                'leadStats'          => $this->dashboard->getDashboardLeadStatistics($user),
                'statistics'         => $this->dashboard->getDashboardAllLeadStatistics($user),
                'dueFollowUps'       => $this->dashboard->getDueFollowUps($user),
                'recentActivities'   => LeadActivity::latest()->take(5)->get(),
            ];
        });

        return view('dashboard.manager', [
            'pageTitle'                    => 'Dashboard',
            'breadcrumbs'                  => [['label' => 'Dashboard']],
            'photos'                       => $this->photos,
            'stats'                        => $dashboardData['stats'],
            'upcomingIncrements'           => $dashboardData['upcomingIncrements'],
            'recentEmployees'              => $dashboardData['recentEmployees'],
            'leadStats'                    => $dashboardData['leadStats'],
            'statistics'                   => $dashboardData['statistics'],
            'dueFollowUps'                 => $dashboardData['dueFollowUps'],
            'todayDuty'                    => $dashboardData['taskCounts']['todayDuty'] ?? 0,
            'overdueDuty'                  => $dashboardData['taskCounts']['overdueDuty'] ?? 0,
            'upcomingDuty'                 => $dashboardData['taskCounts']['upcomingDuty'] ?? 0,
            'approvalPending'              => $dashboardData['taskCounts']['approvalPending'] ?? 0,
            'sendingApproval'              => $dashboardData['taskCounts']['sendingApproval'] ?? 0,
            'adminAssignedTaskCount'       => $dashboardData['adminTaskCounts']['total'] ?? 0,
            'adminOngoingTaskCount'        => $dashboardData['adminTaskCounts']['ongoing'] ?? 0,
            'adminOverdueTaskCount'        => $dashboardData['adminTaskCounts']['overdue'] ?? 0,
            'adminUpcomingTaskCount'       => $dashboardData['adminTaskCounts']['upcoming'] ?? 0,
            'adminApprovalPendingTaskCount' => $dashboardData['adminTaskCounts']['approval_pending'] ?? 0,
            'adminSendingPendingTaskCount' => $dashboardData['adminTaskCounts']['sending_approval'] ?? 0,
            'recentActivities'             => $dashboardData['recentActivities'],
        ]);
    }

    private function getTaskDashboardCounts(User $user): array
    {
        $query = Task::query();

        if ($user->role->value === 'employee') {
            $query->where('assigned_to', $user->id);
        }

        $counts = $query
            ->selectRaw("
                SUM(status = 'ongoing') as todayDuty,
                SUM(status = 'overdue') as overdueDuty,
                SUM(status = 'upcoming') as upcomingDuty,
                SUM(status = 'completed') as sendingApproval
            ")
            ->first();

        $approvalPending = Task::query()
            ->where('status', 'completed')
            ->when(
                $user->role->value === 'employee',
                fn ($q) => $q->where('approved_by', $user->id)
            )
            ->count();

        return [
            'todayDuty'       => (int) ($counts->todayDuty ?? 0),
            'overdueDuty'     => (int) ($counts->overdueDuty ?? 0),
            'upcomingDuty'    => (int) ($counts->upcomingDuty ?? 0),
            'approvalPending' => $approvalPending,
            'sendingApproval' => (int) ($counts->total_completed ?? $counts->sendingApproval ?? 0),
        ];
    }

    private function getAdminAssignedTaskCounts(User $user): array
    {
        $query = Task::query()->where('assigned_to', $user->id);

        $counts = (clone $query)
            ->selectRaw("
                COUNT(*) as total,
                SUM(status = 'ongoing') as ongoing,
                SUM(status = 'overdue') as overdue,
                SUM(status = 'upcoming') as upcoming,
                SUM(status = 'completed') as sending_approval
            ")
            ->first();

        $approvalPending = Task::query()
            ->where('approved_by', $user->id)
            ->where('status', 'completed')
            ->count();

        return [
            'total'            => (int) ($counts->total ?? 0),
            'ongoing'          => (int) ($counts->ongoing ?? 0),
            'overdue'          => (int) ($counts->overdue ?? 0),
            'upcoming'         => (int) ($counts->upcoming ?? 0),
            'sending_approval' => (int) ($counts->sending_approval ?? 0),
            'approval_pending' => $approvalPending,
        ];
    }
}