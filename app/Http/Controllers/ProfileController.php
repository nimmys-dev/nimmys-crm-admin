<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request): View
    {
        return view('profile.index', [
            'pageTitle' => 'Profile',
            'breadcrumbs' => [
                ['label' => 'Profile'],
            ],
            'user' => $request->user(),
        ]);
    }
}
