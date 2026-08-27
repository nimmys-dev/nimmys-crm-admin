<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use App\Services\StaffPhotoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Task;


/**
 * Role-based dashboard.
 *
 * Chooses a view and hands it data from DashboardService. Every figure and
 * every scoping decision lives in that service, so this class holds no
 * business logic and each role gets its own template rather than one view
 * full of @if blocks.
 *
 * Employees never arrive here: the `web.access` middleware ejects them
 * before routing, since they are mobile-only.
 */
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
        $leadStats = $user->canAccessLeadModule() ? $this->dashboard->getLeadStatistics($user) : null;
        $dueFollowUps = $user->canAccessLeadModule() ? $this->dashboard->getDueFollowUps($user) : collect();
         $taskCounts = $this->getTaskDashboardCounts($user);

        return view('dashboard.employee', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [['label' => 'Dashboard']],
            'user' => $user,
            'shop' => $user->shop,
            'leadStats' => $leadStats,
            'dueFollowUps' => $dueFollowUps,
            'todayDuty' => $taskCounts['todayDuty'],
            'overdueDuty' => $taskCounts['overdueDuty'],
            'upcomingDuty' => $taskCounts['upcomingDuty'],
            'approvalPending' => $taskCounts['approvalPending'],
        ]);
    }

    private function adminDashboard(User $user): View
    {
       
        
        $taskCounts = $this->getTaskDashboardCounts($user);

        return view('dashboard.admin', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [['label' => 'Dashboard']],
            'photos' => $this->photos,
            'stats' => $this->dashboard->getAdminStatistics(),
            'upcomingIncrements' => $this->dashboard->getUpcomingIncrements(),
            'recentEmployees' => $this->dashboard->getRecentEmployees(),
            'recentShops' => $this->dashboard->getRecentShops(),
            'leadStats' => $this->dashboard->getLeadStatistics($user),
            'dueFollowUps' => $this->dashboard->getDueFollowUps($user),
            'leadStats' => $this->dashboard->getDashboardLeadStatistics($user),
            'todayDuty' => $taskCounts['todayDuty'],
            'overdueDuty' => $taskCounts['overdueDuty'],
            'upcomingDuty' => $taskCounts['upcomingDuty'],
            'approvalPending' => $taskCounts['approvalPending'],
        ]);
    }
    private function getTaskDashboardCounts(User $user): array
    {
        $query = Task::query();

        // Admin → all tasks
        // Manager / Employee → assigned/approved tasks
        if ($user->role->value !== 'admin') {
            $query->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhere('approved_by', $user->id);
            });
        }

        return [
            'todayDuty' => (clone $query)
                ->where('status', 'ongoing')
                ->count(),

            'overdueDuty' => (clone $query)
                ->where('status', 'overdue')
                ->count(),

            'upcomingDuty' => (clone $query)
                ->where('status', 'upcoming')
                ->count(),

            'approvalPending' => (clone $query)
                ->where('status', 'pending')
                ->count(),
        ];
    }
    private function managerDashboard(User $user): View
    {
        $stats = $this->dashboard->getManagerStatistics($user);
        $shopId = $stats['shop']?->id;
        $taskCounts = $this->getTaskDashboardCounts($user);

        return view('dashboard.manager', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [['label' => 'Dashboard']],
            'photos' => $this->photos,
            'stats' => $stats,

            // Scoped by shop id in the service. A Manager with no shop gets
            // an explicitly empty set rather than an unscoped query.
            'upcomingIncrements' => $shopId
                ? $this->dashboard->getUpcomingIncrements($shopId)
                : collect(),
            'recentEmployees' => $shopId
                ? $this->dashboard->getRecentEmployees($shopId)
                : collect(),

            // Lead figures are scoped by the repository, not by shop — a
            // Manager works the whole pipeline they can see.
            'leadStats' => $this->dashboard->getLeadStatistics($user),
            'dueFollowUps' => $this->dashboard->getDueFollowUps($user),
            'leadStats' => $this->dashboard->getDashboardLeadStatistics($user),
            // Task dashboard counts
        'todayDuty' => $taskCounts['todayDuty'],
        'overdueDuty' => $taskCounts['overdueDuty'],
        'upcomingDuty' => $taskCounts['upcomingDuty'],
        'approvalPending' => $taskCounts['approvalPending'],
        ]);
    }
}
