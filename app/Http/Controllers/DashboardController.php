<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard.
     *
     * Tiles render with null values until the modules are built. Replace each
     * 'value' with a real count as the modules land — the view needs no edits.
     */
    public function index(): View
    {
        return view('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard'],
            ],
            'stats' => [
                ['label' => 'Total Shops', 'value' => null, 'icon' => 'ti ti-building-store'],
                ['label' => 'Total Employees', 'value' => null, 'icon' => 'ti ti-users'],
                ['label' => 'Total Leads', 'value' => null, 'icon' => 'ti ti-target-arrow'],
                ['label' => 'Open Tasks', 'value' => null, 'icon' => 'ti ti-checklist'],
                ["label" => "Today's Follow-ups", 'value' => null, 'icon' => 'ti ti-calendar-event'],
            ],
        ]);
    }
}
