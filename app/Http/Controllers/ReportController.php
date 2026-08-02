<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index', [
            'pageTitle' => 'Reports',
            'breadcrumbs' => [
                ['label' => 'Reports'],
            ],
        ]);
    }
}
