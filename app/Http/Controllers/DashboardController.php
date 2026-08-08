<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use App\Services\StaffPhotoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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

        return $user->isAdmin()
            ? $this->adminDashboard($user)
            : $this->managerDashboard($user);
    }

    private function adminDashboard(User $user): View
    {
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
        ]);
    }

    private function managerDashboard(User $user): View
    {
        $stats = $this->dashboard->getManagerStatistics($user);
        $shopId = $stats['shop']?->id;

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
        ]);
    }
}
