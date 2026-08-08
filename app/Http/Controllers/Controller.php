<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Laravel 11 ships this base class empty, so $this->authorize() and
     * authorizeResource() are unavailable until the trait is added. Policy
     * checks in controllers need it.
     */
    use AuthorizesRequests;
}
