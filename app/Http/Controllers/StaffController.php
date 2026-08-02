<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        return view('staff.index', [
            'pageTitle' => 'Staff Management',
            'breadcrumbs' => [
                ['label' => 'Staff'],
            ],
        ]);
    }
}
