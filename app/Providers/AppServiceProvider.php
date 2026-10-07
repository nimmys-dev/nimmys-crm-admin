<?php

namespace App\Providers;

use App\Contracts\LeadRepository;
use App\Contracts\SalaryIncrementReminderService;
use App\Models\CallDetail;
use App\Models\Lead;
use App\Repositories\EloquentLeadRepository;
use App\Services\DashboardService;
use App\Services\SalaryIncrementService;
use App\View\Composers\CallHistoryComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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

        // Lead reads are scoped by the repository, so binding the contract
        // keeps web and the future mobile API on identical visibility rules.
        $this->app->bind(
            LeadRepository::class,
            EloquentLeadRepository::class,
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

        // The Call Details module plugs its history into the Lead detail page
        // without LeadController having to know it exists.
        View::composer('leads.show', CallHistoryComposer::class);
        Schema::defaultStringLength(191);

        $this->registerDashboardCacheInvalidation();
    }

    /**
     * Drop the cached dashboard figures whenever a lead or a call changes.
     *
     * DashboardService caches its aggregate counts for a short window so the
     * dashboard is not doing six COUNTs on every navigation. Hooking the
     * model events here — rather than adding Cache::forget calls to every
     * service and controller that writes — means no future write path can
     * forget to invalidate, so a cached figure can never outlive the data it
     * was computed from by more than the write itself.
     */
    private function registerDashboardCacheInvalidation(): void
    {
        /*
         * Bump a version counter instead of deleting each key.
         *
         * The per-user keys are "dashboard.lead-stats.user.{id}" and there is
         * no cheap way to enumerate them, nor is the cache store taggable
         * (CACHE_STORE=database). Including this counter in every key means a
         * write simply makes all previous keys unreachable — one integer bump
         * invalidates every user's figures at once, and there is no
         * Cache::flush() that would also throw away sessions and other
         * unrelated entries.
         */
        $invalidate = static function (): void {
            $key = DashboardService::CACHE_VERSION_KEY;

            // increment() returns false and does nothing when the key is
            // absent on some stores, so seed it before the first bump.
            if (Cache::get($key) === null) {
                Cache::forever($key, 1);
            }

            Cache::increment($key);
        };

        foreach ([Lead::class, CallDetail::class] as $model) {
            $model::saved($invalidate);
            $model::deleted($invalidate);
        }
    }
}
