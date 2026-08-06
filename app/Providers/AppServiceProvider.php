<?php

namespace App\Providers;

use App\Contracts\SalaryIncrementReminderService;
use App\Services\SalaryIncrementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Depend on the contract, not the implementation, so the scheduled
        // command and tests can swap it.
        $this->app->bind(
            SalaryIncrementReminderService::class,
            SalaryIncrementService::class,
        );

        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel's bundled paginator views emit Tailwind utilities that the
        // theme's purged style.css does not contain, so they would render
        // unstyled. This view uses the theme's own .pagination classes.
        Paginator::defaultView('vendor.pagination.crm');
        Paginator::defaultSimpleView('vendor.pagination.crm');

        // Fail loudly in development when a relationship is used without
        // being eager loaded, rather than shipping an N+1 to production.
        Model::preventLazyLoading(! app()->isProduction());
        Schema::defaultStringLength(191);
    }
}
