<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Backs the header search box. Returns an empty result set until the
     * modules define what is searchable.
     */
    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        return view('search.index', [
            'pageTitle' => 'Search',
            'breadcrumbs' => [
                ['label' => 'Search'],
            ],
            'term' => $term,
            'results' => collect(),
        ]);
    }
}
