<?php

namespace Tests\Feature;

use App\Enums\ShopStatus;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShopManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'SHP-001',
            'name' => 'Central Store',
            'manager_id' => null,
            'email' => 'central@example.com',
            'phone' => '9876543210',
            'address_line' => '12 Market Road',
            'city' => 'Kochi',
            'state' => 'Kerala',
            'postal_code' => '682001',
            'country' => 'India',
            'opened_on' => '2024-01-15',
            'status' => ShopStatus::Active->value,
            'notes' => null,
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization — Admin only
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('shops.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_reach_every_shop_route(): void
    {
        $shop = Shop::factory()->create();

        $this->actingAs($this->admin())->get(route('shops.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('shops.create'))->assertOk();
        $this->actingAs($this->admin())->get(route('shops.show', $shop))->assertOk();
        $this->actingAs($this->admin())->get(route('shops.edit', $shop))->assertOk();
    }

    #[Test]
    public function manager_is_forbidden_from_every_shop_route(): void
    {
        $manager = User::factory()->manager()->create();
        $shop = Shop::factory()->create();

        $this->actingAs($manager)->get(route('shops.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('shops.create'))->assertForbidden();
        $this->actingAs($manager)->get(route('shops.show', $shop))->assertForbidden();
        $this->actingAs($manager)->get(route('shops.edit', $shop))->assertForbidden();
        $this->actingAs($manager)->post(route('shops.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($manager)->delete(route('shops.destroy', $shop))->assertForbidden();

        $this->assertDatabaseCount('shops', 1);
    }

    #[Test]
    public function employee_is_forbidden_from_shops_module(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)->get(route('shops.index'))->assertForbidden();
    }

    #[Test]
    public function a_suspended_admin_loses_access(): void
    {
        $admin = User::factory()->admin()->suspended()->create();

        $this->actingAs($admin)->get(route('shops.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function admin_can_create_a_shop(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('shops.store'), $this->validPayload());

        $shop = Shop::firstWhere('code', 'SHP-001');

        $this->assertNotNull($shop);
        $response->assertRedirect(route('shops.show', $shop));
        $this->assertSame('Central Store', $shop->name);
        $this->assertSame(ShopStatus::Active, $shop->status);
    }

    #[Test]
    public function the_shop_code_is_uppercased_before_saving(): void
    {
        $this->actingAs($this->admin())
            ->post(route('shops.store'), $this->validPayload(['code' => 'shp-lower']));

        $this->assertDatabaseHas('shops', ['code' => 'SHP-LOWER']);
    }

    #[Test]
    public function the_shop_code_must_be_unique(): void
    {
        Shop::factory()->create(['code' => 'SHP-001']);

        $this->actingAs($this->admin())
            ->post(route('shops.store'), $this->validPayload())
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('shops', 1);
    }

    #[Test]
    public function required_fields_are_enforced(): void
    {
        $this->actingAs($this->admin())
            ->post(route('shops.store'), ['code' => '', 'name' => '', 'status' => ''])
            ->assertSessionHasErrors(['code', 'name', 'status']);
    }

    #[Test]
    public function an_employee_cannot_be_assigned_as_shop_manager(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($this->admin())
            ->post(route('shops.store'), $this->validPayload(['manager_id' => $employee->id]))
            ->assertSessionHasErrors('manager_id');
    }

    #[Test]
    public function a_manager_can_be_assigned_as_shop_manager(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($this->admin())
            ->post(route('shops.store'), $this->validPayload(['manager_id' => $manager->id]));

        $this->assertDatabaseHas('shops', [
            'code' => 'SHP-001',
            'manager_id' => $manager->id,
        ]);
    }

    #[Test]
    public function the_manager_dropdown_offers_ids_not_names(): void
    {
        /*
         * The earlier tests all posted manager_id directly, so they never
         * exercised what the form actually renders. <x-form.select> was
         * emitting the manager's name as the option value, which the
         * exists: rule then rejected with "must be an Admin or Manager".
         */
        $manager = User::factory()->manager()->create(['name' => 'Mo Manager']);

        $html = $this->actingAs($this->admin())
            ->get(route('shops.create'))
            ->getContent();

        $this->assertStringContainsString('value="'.$manager->id.'"', $html);
        $this->assertStringNotContainsString('value="Mo Manager"', $html);
    }

    #[Test]
    public function submitting_the_rendered_form_assigns_the_manager(): void
    {
        // End-to-end guard: scrape the option value out of the real form and
        // post exactly that, the way a browser would.
        $manager = User::factory()->manager()->create(['name' => 'Mo Manager']);
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('shops.create'))->getContent();

        preg_match('/<option value="([^"]*)"[^>]*>\s*Mo Manager/', $html, $matches);
        $postedValue = $matches[1] ?? null;

        $this->assertNotNull($postedValue, 'The manager option was not rendered.');

        $this->actingAs($admin)
            ->post(route('shops.store'), $this->validPayload(['manager_id' => $postedValue]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shops', [
            'code' => 'SHP-001',
            'manager_id' => $manager->id,
        ]);
    }

    #[Test]
    public function an_unassigned_manager_is_stored_as_null(): void
    {
        // The placeholder option submits an empty string.
        $this->actingAs($this->admin())
            ->post(route('shops.store'), $this->validPayload(['manager_id' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('shops', [
            'code' => 'SHP-001',
            'manager_id' => null,
        ]);
    }

    #[Test]
    public function the_opening_date_cannot_be_in_the_future(): void
    {
        $this->actingAs($this->admin())
            ->post(route('shops.store'), $this->validPayload([
                'opened_on' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('opened_on');
    }

    /*
    |--------------------------------------------------------------------------
    | Update & delete
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function admin_can_update_a_shop(): void
    {
        $shop = Shop::factory()->create(['code' => 'SHP-001', 'name' => 'Old Name']);

        $this->actingAs($this->admin())
            ->put(route('shops.update', $shop), $this->validPayload(['name' => 'New Name']))
            ->assertRedirect(route('shops.show', $shop));

        $this->assertSame('New Name', $shop->fresh()->name);
    }

    #[Test]
    public function a_shop_keeps_its_own_code_on_update(): void
    {
        // The unique rule must ignore the record being edited, otherwise
        // saving without changing the code would fail.
        $shop = Shop::factory()->create(['code' => 'SHP-001']);

        $this->actingAs($this->admin())
            ->put(route('shops.update', $shop), $this->validPayload(['code' => 'SHP-001']))
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function deleting_a_shop_soft_deletes_it(): void
    {
        $shop = Shop::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('shops.destroy', $shop))
            ->assertRedirect(route('shops.index'));

        $this->assertSoftDeleted($shop);
    }

    #[Test]
    public function deleting_a_shop_detaches_staff_rather_than_deleting_them(): void
    {
        $shop = Shop::factory()->create();
        $staff = User::factory()->employee()->create(['shop_id' => $shop->id]);

        $this->actingAs($this->admin())->delete(route('shops.destroy', $shop));

        // Soft delete leaves the row, so staff keep their association and the
        // shop remains restorable.
        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Search, filter & sort
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function search_matches_name_code_and_city(): void
    {
        Shop::factory()->create(['name' => 'Marine Drive Outlet', 'code' => 'MDO-1', 'city' => 'Kochi']);
        Shop::factory()->create(['name' => 'Hill Palace Store', 'code' => 'HPS-1', 'city' => 'Tripunithura']);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('shops.index', ['q' => 'Marine']))
            ->assertSee('Marine Drive Outlet')->assertDontSee('Hill Palace Store');

        $this->actingAs($admin)->get(route('shops.index', ['q' => 'HPS-1']))
            ->assertSee('Hill Palace Store')->assertDontSee('Marine Drive Outlet');

        $this->actingAs($admin)->get(route('shops.index', ['q' => 'Kochi']))
            ->assertSee('Marine Drive Outlet')->assertDontSee('Hill Palace Store');
    }

    #[Test]
    public function the_status_filter_narrows_results(): void
    {
        Shop::factory()->create(['name' => 'Open Shop']);
        Shop::factory()->inactive()->create(['name' => 'Closed Shop']);

        $this->actingAs($this->admin())
            ->get(route('shops.index', ['status' => ShopStatus::Inactive->value]))
            ->assertSee('Closed Shop')
            ->assertDontSee('Open Shop');
    }

    #[Test]
    public function an_unknown_sort_column_is_rejected(): void
    {
        // `sort` reaches an ORDER BY, so anything off the whitelist must fail
        // validation rather than be interpolated.
        $this->actingAs($this->admin())
            ->get(route('shops.index', ['sort' => 'password']))
            ->assertSessionHasErrors('sort');
    }

    #[Test]
    public function results_can_be_sorted_by_a_whitelisted_column(): void
    {
        Shop::factory()->create(['name' => 'Zeta Store']);
        Shop::factory()->create(['name' => 'Alpha Store']);

        $response = $this->actingAs($this->admin())
            ->get(route('shops.index', ['sort' => 'name', 'direction' => 'asc']));

        $response->assertOk();
        $this->assertLessThan(
            strpos($response->getContent(), 'Zeta Store'),
            strpos($response->getContent(), 'Alpha Store'),
        );
    }

    #[Test]
    public function a_search_term_with_like_wildcards_is_escaped(): void
    {
        Shop::factory()->create(['name' => 'Real Shop']);

        // An unescaped '%' would match every row.
        $this->actingAs($this->admin())
            ->get(route('shops.index', ['q' => '%']))
            ->assertDontSee('Real Shop');
    }
}
