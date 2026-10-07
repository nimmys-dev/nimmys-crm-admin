<?php

/**
 * End-to-end check of the Dashboard and Leads pages.
 *
 * Boots the real app and dispatches real requests through the HTTP kernel, so
 * middleware, policies, controllers and views all run exactly as they do in a
 * browser. Reports status, query count and any exception per URL.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Bootstrap just the container + config + database, so Eloquent is usable.
// (The console kernel is avoided: it loads a dev-only debugbar provider that
// is not installed in this environment.)
$app->make(Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class)->bootstrap($app);
$app->make(Illuminate\Foundation\Bootstrap\LoadConfiguration::class)->bootstrap($app);
$app->make(Illuminate\Foundation\Bootstrap\HandleExceptions::class)->bootstrap($app);
$app->make(Illuminate\Foundation\Bootstrap\RegisterFacades::class)->bootstrap($app);
$app->make(Illuminate\Foundation\Bootstrap\RegisterProviders::class)->bootstrap($app);
$app->make(Illuminate\Foundation\Bootstrap\BootProviders::class)->bootstrap($app);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Log in as the admin without needing a password. */
$admin = App\Models\User::where('role', 'admin')->first() ?? App\Models\User::first();

$urls = [
    'dashboard (admin)' => '/dashboard',
    'leads index' => '/leads',
    'leads index - default filter' => '/leads?filter=total_leads',
    'leads - my_unattended' => '/leads?filter=my_unattended',
    'leads - my_today_followup' => '/leads?filter=my_today_followup',
    'leads - my_overdue_followup' => '/leads?filter=my_overdue_followup',
    'leads - my_upcoming_followup' => '/leads?filter=my_upcoming_followup',
    'leads - my_leads' => '/leads?filter=my_leads',
    'leads - all_unattended' => '/leads?filter=all_unattended',
    'leads - all_today_followup' => '/leads?filter=all_today_followup',
    'leads - all_overdue_followup' => '/leads?filter=all_overdue_followup',
    'leads - all_upcoming_followup' => '/leads?filter=all_upcoming_followup',
    'leads - search by name' => '/leads?q=test',
    'leads - search by phone' => '/leads?q=99',
    'leads - search wildcard %%' => '/leads?q=%25',
    'leads - search underscore _' => '/leads?q=_',
    'leads - search assigned user' => '/leads?q=admin',
    'leads - sort by name asc' => '/leads?sort=name&direction=asc',
    'leads - bogus sort (must be ignored)' => '/leads?sort=password&direction=asc',
    'leads - bogus filter (must be rejected)' => '/leads?filter=../../etc/passwd',
    'leads - per_page 25' => '/leads?per_page=25',
    'leads closed' => '/leads-closed',
    'lead show' => '/leads/' . (App\Models\Lead::first()?->id ?? 1),
    'tasks index' => '/tasks',
    'reports lead-management' => '/reports/lead-management',
];

$pass = 0;
$fail = 0;

foreach ($urls as $label => $url) {
    $request = Request::create($url, 'GET');
    $request->setUserResolver(fn () => $admin);

    // Authenticate the request the same way the session guard would.
    auth()->login($admin);

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $queries = count(DB::getQueryLog());

        $ok = $status >= 200 && $status < 400;

        if ($ok) {
            $pass++;
        } else {
            $fail++;
        }

        echo sprintf(
            "  %s  %-38s status=%d queries=%d\n",
            $ok ? 'PASS' : 'FAIL',
            $label,
            $status,
            $queries
        );
    } catch (\Throwable $e) {
        $fail++;
        echo sprintf(
            "  FAIL  %-38s %s :: %s\n",
            $label,
            get_class($e),
            substr($e->getMessage(), 0, 300)
        );
    } finally {
        DB::disableQueryLog();
    }
}

echo "\n=== simulation: dashboard loaded twice in a row (cache check) ===\n";
foreach ([1, 2] as $loadNumber) {
    $request = Request::create('/dashboard', 'GET');
    $request->setUserResolver(fn () => $admin);
    auth()->login($admin);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $response = $kernel->handle($request);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    echo "  dashboard load #{$loadNumber}: status=" . $response->getStatusCode() . " queries={$queryCount}\n";
}

echo "\n=== result: {$pass} passed, {$fail} failed ===\n";
exit($fail === 0 ? 0 : 1);
