<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function service(): DashboardService
    {
        return app(DashboardService::class);
    }

    /**
     * A manager plus the shop they run.
     *
     * @return array{0: User, 1: Shop}
     */
    private function managerWithShop(): array
    {
        $manager = User::factory()->manager()->create();
        $shop = Shop::factory()->create(['manager_id' => $manager->id]);

        return [$manager->fresh(), $shop];
    }

    /*
    |--------------------------------------------------------------------------
    | Access & routing
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    #[Test]
    public function an_employee_is_ejected_from_the_web_dashboard(): void
    {
        $this->actingAs(User::factory()->employee()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    #[Test]
    public function an_admin_gets_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.admin');
    }

    #[Test]
    public function a_manager_gets_the_manager_dashboard(): void
    {
        [$manager] = $this->managerWithShop();

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.manager');
    }

    #[Test]
    public function a_suspended_admin_cannot_reach_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->suspended()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    /*
    |--------------------------------------------------------------------------
    | Admin statistics
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function admin_statistics_count_every_role_and_lead_access_split(): void
    {
        Shop::factory()->count(2)->create();
        Shop::factory()->inactive()->create();

        User::factory()->admin()->create();
        User::factory()->manager()->count(2)->create();
        User::factory()->employee()->withLeadAccess()->count(3)->create();
        User::factory()->employee()->count(4)->create();

        $stats = $this->service()->getAdminStatistics();

        $this->assertSame(3, $stats['shops']);
        $this->assertSame(2, $stats['active_shops']);
        $this->assertSame(2, $stats['managers']);
        $this->assertSame(7, $stats['employees']);
        $this->assertSame(3, $stats['employees_with_lead_access']);
        $this->assertSame(4, $stats['employees_without_lead_access']);
    }

    #[Test]
    public function the_lead_access_split_always_totals_the_employee_count(): void
    {
        User::factory()->employee()->withLeadAccess()->count(2)->create();
        User::factory()->employee()->count(5)->create();

        $stats = $this->service()->getAdminStatistics();

        $this->assertSame(
            $stats['employees'],
            $stats['employees_with_lead_access'] + $stats['employees_without_lead_access']
        );
    }

    #[Test]
    public function soft_deleted_staff_are_excluded_from_the_counts(): void
    {
        User::factory()->employee()->count(3)->create();
        User::factory()->employee()->create()->delete();

        $this->assertSame(3, $this->service()->getAdminStatistics()['employees']);
    }

    /*
    |--------------------------------------------------------------------------
    | Manager scoping
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function manager_statistics_are_scoped_to_their_own_shop(): void
    {
        [$manager, $shop] = $this->managerWithShop();
        $otherShop = Shop::factory()->create();

        User::factory()->employee()->count(2)->create(['shop_id' => $shop->id]);
        User::factory()->employee()->withLeadAccess()->create(['shop_id' => $shop->id]);
        User::factory()->employee()->count(5)->create(['shop_id' => $otherShop->id]);

        $stats = $this->service()->getManagerStatistics($manager);

        $this->assertSame($shop->id, $stats['shop']->id);
        $this->assertSame(3, $stats['employees'], 'Only staff in the manager\'s own shop count.');
        $this->assertSame(1, $stats['employees_with_lead_access']);
        $this->assertSame(2, $stats['employees_without_lead_access']);
    }

    #[Test]
    public function a_manager_never_sees_another_shops_staff_on_the_page(): void
    {
        [$manager, $shop] = $this->managerWithShop();
        $otherShop = Shop::factory()->create();

        User::factory()->employee()->create(['shop_id' => $shop->id, 'name' => 'Mine Alice']);
        User::factory()->employee()->create(['shop_id' => $otherShop->id, 'name' => 'Theirs Bob']);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertSee('Mine Alice')
            ->assertDontSee('Theirs Bob');
    }

    #[Test]
    public function a_manager_never_sees_another_shops_increments(): void
    {
        [$manager, $shop] = $this->managerWithShop();
        $otherShop = Shop::factory()->create();

        User::factory()->employee()->incrementDueIn(2)->create([
            'shop_id' => $shop->id, 'name' => 'Mine Alice',
        ]);
        User::factory()->employee()->incrementDueIn(2)->create([
            'shop_id' => $otherShop->id, 'name' => 'Theirs Bob',
        ]);

        $stats = $this->service()->getManagerStatistics($manager);

        $this->assertSame(1, $stats['upcoming_increments']);
        $this->assertSame(
            ['Mine Alice'],
            $this->service()->getUpcomingIncrements($shop->id)->pluck('name')->all()
        );
    }

    #[Test]
    public function a_manager_with_no_shop_gets_zeroes_not_global_figures(): void
    {
        // Falling back to unscoped queries here would leak the whole org.
        $manager = User::factory()->manager()->create();
        User::factory()->employee()->count(4)->create();

        $stats = $this->service()->getManagerStatistics($manager);

        $this->assertNull($stats['shop']);
        $this->assertSame(0, $stats['employees']);
        $this->assertSame(0, $stats['upcoming_increments']);
    }

    #[Test]
    public function a_manager_with_no_shop_sees_an_explanation(): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No shop is assigned to your account yet.');
    }

    #[Test]
    public function the_shop_assignment_is_used_when_no_managed_shop_exists(): void
    {
        // shops.manager_id is the primary link; users.shop_id is the fallback.
        $shop = Shop::factory()->create();
        $manager = User::factory()->manager()->create(['shop_id' => $shop->id]);

        $this->assertSame($shop->id, $this->service()->shopFor($manager)?->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Upcoming increments
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_increment_widget_respects_the_reminder_window_and_flag(): void
    {
        $inWindow = User::factory()->employee()->incrementDueIn(3)->create(['name' => 'In Window']);
        User::factory()->employee()->incrementDueIn(30)->create(['name' => 'Too Far']);
        User::factory()->employee()->incrementDueIn(2)->create([
            'name' => 'Reminder Off', 'increment_notification' => false,
        ]);

        $names = $this->service()->getUpcomingIncrements()->pluck('name');

        $this->assertSame(['In Window'], $names->all());
        $this->assertTrue($inWindow->isDueForIncrementReminder());
    }

    #[Test]
    public function upcoming_increments_are_ordered_soonest_first(): void
    {
        User::factory()->employee()->incrementDueIn(4)->create(['name' => 'Later']);
        User::factory()->employee()->incrementDueIn(1)->create(['name' => 'Sooner']);

        $this->assertSame(
            ['Sooner', 'Later'],
            $this->service()->getUpcomingIncrements()->pluck('name')->all()
        );
    }

    #[Test]
    public function the_increment_widget_renders_every_required_column(): void
    {
        User::factory()->employee()->incrementDueIn(3)->create(['name' => 'John Doe']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee('John Doe')
            ->assertSee('25,000.00')      // current salary
            ->assertSee('+2,500.00')      // increment amount
            ->assertSee('3 days');        // days remaining
    }

    #[Test]
    public function an_increment_due_today_reads_as_today(): void
    {
        User::factory()->employee()->incrementDueIn(0)->create(['name' => 'Due Now']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee('Due Now')
            ->assertSee('Today');
    }

    /*
    |--------------------------------------------------------------------------
    | Listings
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function recent_listings_are_newest_first_and_capped(): void
    {
        User::factory()->employee()->count(8)->create();
        Shop::factory()->count(8)->create();

        $employees = $this->service()->getRecentEmployees();
        $shops = $this->service()->getRecentShops();

        $this->assertCount(DashboardService::LIST_LIMIT, $employees);
        $this->assertCount(DashboardService::LIST_LIMIT, $shops);
        $this->assertTrue($employees->first()->created_at >= $employees->last()->created_at);
    }

    #[Test]
    public function recent_shops_are_admin_only(): void
    {
        [$manager] = $this->managerWithShop();
        Shop::factory()->create(['name' => 'Zebra Trading Post']);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertDontSee('Recently created shops')
            ->assertDontSee('Zebra Trading Post');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee('Recently created shops');
    }

    #[Test]
    public function a_manager_gets_no_links_into_the_staff_module(): void
    {
        // Managers are forbidden from /staff, so linking there would 403.
        [$manager, $shop] = $this->managerWithShop();
        $employee = User::factory()->employee()->create(['shop_id' => $shop->id]);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('staff.show', $employee))
            ->assertDontSee(route('staff.index'));
    }

    /*
    |--------------------------------------------------------------------------
    | Placeholders & efficiency
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function unbuilt_module_widgets_render_as_placeholders(): void
    {
        /*
         * Lead and follow-up statistics graduated to real widgets when the
         * Lead module landed — see LeadManagementTest. Task is the only
         * placeholder left.
         */
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee('Task statistics')
            ->assertSee('Available once the Task module is built.');
    }

    #[Test]
    public function the_lead_widgets_are_real_not_placeholders(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee('Lead statistics')
            ->assertSee('Follow-ups due')
            ->assertDontSee('Available once the Lead module is built.');
    }

    #[Test]
    public function the_dashboard_holds_a_flat_query_budget(): void
    {
        /*
         * Guards against N+1 as the dashboard grows: the widgets eager load
         * their relations, so adding rows must not add queries. Compared
         * against a larger dataset rather than asserting an exact number,
         * which would break on any unrelated change.
         */
        $admin = User::factory()->admin()->create();

        $count = function () use ($admin) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($admin)->get(route('dashboard'))->assertOk();
            $queries = count(DB::getRawQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $small = $count();

        Shop::factory()->count(5)->create();
        User::factory()->employee()->incrementDueIn(2)->count(10)->create();

        $this->assertSame($small, $count(), 'Query count must not grow with the number of rows.');
    }
}
