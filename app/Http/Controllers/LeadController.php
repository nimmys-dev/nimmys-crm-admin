<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LeadController extends Controller
{
    public function index(): View
    {
        return view('leads.index', [
            'pageTitle' => 'Lead Management',
            'breadcrumbs' => [
                ['label' => 'Leads'],
            ],
        ]);
    }
}
