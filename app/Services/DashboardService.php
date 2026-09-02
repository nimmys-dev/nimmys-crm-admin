<?php

namespace App\Services;

use App\Contracts\LeadRepository;
use App\Enums\ShopStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Every dashboard statistic and listing.
 *
 * All scoping lives here rather than in the controller or the views: a
 * Manager's figures are narrowed to their own shop by the query itself, so
 * there is no way for a view to render data the role should not see.
 */
class DashboardService
{
    /** Rows shown in each dashboard listing. */
    public const LIST_LIMIT = 5;

    public function __construct(private readonly LeadRepository $leads) {}

    /**
     * Lead pipeline figures for the viewer.
     *
     * Delegated to LeadRepository so the dashboard inherits the same
     * visibility scoping as the Lead module itself — a Manager sees the whole
     * pipeline, an Employee only their own.
     *
     * @return array<string, mixed>
     */
    public function getLeadStatistics(User $viewer): array
    {
        return $this->leads->statistics($viewer);
    }

    /**
     * Leads whose follow-up is due today or overdue.
     *
     * @return Collection<int, Lead>
     */
    public function getDueFollowUps(User $viewer, int $limit = self::LIST_LIMIT): Collection
    {
        return $this->leads->dueForFollowUp($viewer, 0, $limit);
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    /**
     * Organisation-wide figures for an Admin.
     *
     * The five headcount figures come from one aggregate query rather than
     * five COUNTs — the table is scanned once and the conditional sums are
     * computed in the same pass.
     *
     * @return array<string, int>
     */
    public function getAdminStatistics(): array
    {
        $headcount = $this->headcount();

        return [
            'shops' => Shop::query()->count(),
            'active_shops' => Shop::query()->where('status', ShopStatus::Active)->count(),
            'managers' => $headcount['managers'],
            'employees' => $headcount['employees'],
            'employees_with_lead_access' => $headcount['with_lead_access'],
            'employees_without_lead_access' => $headcount['without_lead_access'],
            'upcoming_increments' => $this->upcomingIncrementQuery()->count(),
       
        ];
    }

    // public function getDashboardLeadStatistics(User $user): array
    // {
    //     $query = Lead::query();
    //     return [
    //         'unattended' => (clone $query)
    //             ->whereNull('assigned_to')
    //             ->count(),

    //         'today_followup' => (clone $query)
    //             ->whereDate('next_follow_up_at', today())
    //             ->count(),

    //         'overdue_followup' => (clone $query)
    //             ->whereDate('next_follow_up_at', '<', today())
    //             ->count(),

    //         'upcoming_followup' => (clone $query)
    //             ->whereDate('next_follow_up_at', '>', today())
    //             ->count(),
    //             // Logged-in user's leads
    //         'your_leads' => Lead::query()
    //             ->where('assigned_to', $user->id)
    //             ->count(),

    //         // Admin: ALL leads
    //         'total_leads' => Lead::query()
    //             ->count(),
    //         ];
    // }


public function getDashboardLeadStatistics(User $user): array
{
    // Role check controller-ൽ ഉള്ളതുപോലെ തന്നെ uniform ആക്കുക
    // $isAdmin = (isset($user->role->value) && $user->role->value === 'admin') || $user->isAdmin();

    // $query = Lead::query();

    // if (!$isAdmin) {
    //     $query->where('assigned_to', $user->id);
    // }

    $query = Lead::query()
        ->where('assigned_to', $user->id)
        ->where('status', '!=', 'closed') ->where('status', '!=', 'lost');


    return [
        'unattended' => (clone $query)
            ->whereDoesntHave('callDetails')
            ->count(),

        // callDetails ന് പകരം latestCall ഉപയോഗിക്കുക
        'today_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', today());
            })
            ->count(),

        'overdue_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '<', today());
            })
            ->count(),

        'upcoming_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '>', today());
            })
            ->count(),

        'your_leads' => Lead::query()
            ->where('assigned_to', $user->id)
            ->where('status', '!=', 'closed')
             ->where('status', '!=', 'lost')
            ->count(),

        // 'total_leads' => Lead::query()->count(),
        'total_leads' => Lead::query() ->where('status', '!=', 'closed')  ->where('status', '!=', 'lost')->count(),
    ];
}

public function getDashboardAllLeadStatistics(User $user): array
{
    $query = Lead::query()
        ->where('status', '!=', 'lost')
        ->where('status', '!=', 'closed');

    return [

        'unattended' => (clone $query)
            ->whereDoesntHave('callDetails')
            ->count(),

        'today_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', today());
            })
            ->count(),

        'overdue_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '<', today());
            })
            ->count(),

        'upcoming_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '>', today());
            })
            ->count(),

        'total_leads' => (clone $query)->count(),
    ];
}

//     public function getDashboardLeadStatistics(User $user): array
// {
//     $query = Lead::query();

//     // Admin → all leads
//     // Manager / Employee → logged-in user's leads
//     if (!$user->isAdmin()) {
//         $query->where('assigned_to', $user->id);
//     }

//     return [
//         'unattended' => (clone $query)
//             ->whereNull('assigned_to')
//             ->count(),

//         'today_followup' => (clone $query)
//             ->whereDate('next_follow_up_at', today())
//             ->count(),

//         'overdue_followup' => (clone $query)
//             ->whereDate('next_follow_up_at', '<', today())
//             ->count(),

//         'upcoming_followup' => (clone $query)
//             ->whereDate('next_follow_up_at', '>', today())
//             ->count(),

//         // Logged-in user's leads
//         'your_leads' => Lead::query()
//             ->where('assigned_to', $user->id)
//             ->count(),

//         // All roles → all leads
//         'total_leads' => Lead::query()
//             ->count(),
//     ];
// }


    /**
     * Figures narrowed to the shop this Manager runs.
     *
     * @return array<string, mixed>
     */
    public function getManagerStatistics(User $manager): array
    {
        $shop = $this->shopFor($manager);

        if (! $shop) {
            return [
                'shop' => null,
                'employees' => 0,
                'employees_with_lead_access' => 0,
                'employees_without_lead_access' => 0,
                'upcoming_increments' => 0,
            ];
        }

        $headcount = $this->headcount($shop->id);

        return [
            'shop' => $shop,
            'employees' => $headcount['employees'],
            'employees_with_lead_access' => $headcount['with_lead_access'],
            'employees_without_lead_access' => $headcount['without_lead_access'],
            'upcoming_increments' => $this->upcomingIncrementQuery($shop->id)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Listings
    |--------------------------------------------------------------------------
    */

    /**
     * Staff whose increment falls inside the reminder window, nearest first.
     *
     * Reuses User::scopeDueForIncrementReminder so the dashboard and the
     * reminder job can never drift apart on what "upcoming" means.
     *
     * @return Collection<int, User>
     */
    public function getUpcomingIncrements(?int $shopId = null, int $limit = self::LIST_LIMIT): Collection
    {
        return $this->upcomingIncrementQuery($shopId)
            ->with('shop:id,name')
            ->orderBy('increment_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Most recently added staff.
     *
     * @return Collection<int, User>
     */
    public function getRecentEmployees(?int $shopId = null, int $limit = self::LIST_LIMIT): Collection
    {
        return User::query()
            ->with('shop:id,name')
            ->when($shopId, fn (Builder $query) => $query->where('shop_id', $shopId))
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Most recently created shops. Admin-only — a Manager has just one shop.
     *
     * @return Collection<int, Shop>
     */
    public function getRecentShops(int $limit = self::LIST_LIMIT): Collection
    {
        return Shop::query()
            ->withCount('staff')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * The shop a Manager is responsible for.
     *
     * Prefers the shop they are set as manager of (shops.manager_id), and
     * falls back to the shop they are assigned to (users.shop_id) so the
     * dashboard still works if only the assignment was filled in.
     */
    public function shopFor(User $manager): ?Shop
    {
        return $manager->managedShop
            ?? ($manager->shop_id ? $manager->shop()->first() : null);
    }

    /**
     * Headcount by role and lead access in a single pass.
     *
     * CASE WHEN rather than SUM(condition) so the expression is portable
     * across MySQL and the SQLite used by the test suite.
     *
     * @return array{managers: int, employees: int, with_lead_access: int, without_lead_access: int}
     */
    private function headcount(?int $shopId = null): array
    {
        $employee = UserRole::Employee->value;
        $manager = UserRole::Manager->value;

        $row = User::query()
            ->when($shopId, fn (Builder $query) => $query->where('shop_id', $shopId))
            ->selectRaw(
                'SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) AS managers,'
                .'SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) AS employees,'
                .'SUM(CASE WHEN role = ? AND lead_module_access = 1 THEN 1 ELSE 0 END) AS with_lead_access,'
                .'SUM(CASE WHEN role = ? AND lead_module_access = 0 THEN 1 ELSE 0 END) AS without_lead_access',
                [$manager, $employee, $employee, $employee]
            )
            ->first();

        return [
            'managers' => (int) ($row->managers ?? 0),
            'employees' => (int) ($row->employees ?? 0),
            'with_lead_access' => (int) ($row->with_lead_access ?? 0),
            'without_lead_access' => (int) ($row->without_lead_access ?? 0),
        ];
    }

    /**
     * @return Builder<User>
     */
    private function upcomingIncrementQuery(?int $shopId = null): Builder
    {
        return User::query()
            ->dueForIncrementReminder()
            ->when($shopId, fn (Builder $query) => $query->where('shop_id', $shopId));
    }
}
