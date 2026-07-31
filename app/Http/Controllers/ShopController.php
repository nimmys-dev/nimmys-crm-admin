<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ShopController extends Controller
{
    public function index(): View
    {
        return view('shops.index', [
            'pageTitle' => 'Shop Management',
            'breadcrumbs' => [
                ['label' => 'Shops'],
            ],
        ]);
    }
}
