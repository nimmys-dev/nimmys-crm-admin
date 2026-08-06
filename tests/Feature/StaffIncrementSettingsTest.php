<?php

namespace Tests\Feature;

use App\Contracts\SalaryIncrementReminderService;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\SalaryIncrementReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Salary increment settings, the reminder architecture, and Lead module
 * access gating on the Staff module.
 */
class StaffIncrementSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Priya Nair',
            'email' => 'priya@example.com',
            'role' => UserRole::Employee->value,
            'phone' => '9876543210',
            'salary' => '25000.00',
            'status' => UserStatus::Active->value,
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Fields & casting
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function increment_fields_are_saved_and_cast(): void
    {
        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload([
                'increment_date' => today()->addDays(30)->toDateString(),
                'increment_amount' => '2500.00',
            ]))
            ->assertSessionHasNoErrors();

        $member = User::firstWhere('email', 'priya@example.com');

        $this->assertSame('2500.00', $member->increment_amount);
        $this->assertTrue($member->increment_date->isSameDay(today()->addDays(30)));
        $this->assertIsBool($member->increment_notification);
        $this->assertIsBool($member->lead_module_access);
    }

    #[Test]
    public function the_projected_salary_adds_the_increment(): void
    {
        $member = User::factory()->employee()->create([
            'salary' => '25000.00',
            'increment_amount' => '2500.00',
        ]);

        $this->assertSame('27500.00', $member->projectedSalary());
    }

    #[Test]
    public function the_increment_date_cannot_be_in_the_past(): void
    {
        // A past date would sit inside the reminder window forever.
        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload([
                'increment_date' => today()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('increment_date');
    }

    #[Test]
    public function a_negative_increment_amount_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload(['increment_amount' => '-100']))
            ->assertSessionHasErrors('increment_amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Toggles
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_reminder_defaults_to_on_and_lead_access_to_off(): void
    {
        $member = User::factory()->employee()->create();

        $this->assertTrue($member->increment_notification);
        $this->assertFalse($member->lead_module_access);
    }

    #[Test]
    public function an_admin_can_switch_both_toggles_on(): void
    {
        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload([
                'increment_notification' => '1',
                'lead_module_access' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $member = User::firstWhere('email', 'priya@example.com');

        $this->assertTrue($member->increment_notification);
        $this->assertTrue($member->lead_module_access);
    }

    #[Test]
    public function a_toggle_can_be_switched_back_off(): void
    {
        /*
         * Regression guard: an unticked checkbox is absent from the payload.
         * Without the hidden "0" companion input the key never arrives and
         * the toggle can be switched on but never off.
         */
        $member = User::factory()->employee()->create([
            'increment_notification' => true,
            'lead_module_access' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('staff.update', $member), $this->payload([
                'email' => $member->email,
                'password' => '',
                'password_confirmation' => '',
                'increment_notification' => '0',
                'lead_module_access' => '0',
            ]))
            ->assertSessionHasNoErrors();

        $member->refresh();

        $this->assertFalse($member->increment_notification);
        $this->assertFalse($member->lead_module_access);
    }

    #[Test]
    public function the_form_renders_the_hidden_companion_input(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('staff.create'))
            ->getContent();

        $this->assertStringContainsString('name="lead_module_access" value="0"', $html);
        $this->assertStringContainsString('name="increment_notification" value="0"', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization on the privileged toggles
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function only_admin_holds_the_settings_ability(): void
    {
        $this->assertTrue($this->admin()->can('staff.settings.manage'));
        $this->assertFalse(User::factory()->manager()->create()->can('staff.settings.manage'));
        $this->assertFalse(User::factory()->employee()->create()->can('staff.settings.manage'));
    }

    #[Test]
    public function a_user_without_the_settings_ability_cannot_post_the_toggles(): void
    {
        /*
         * Hiding the inputs is not enough — the fields can be posted directly.
         * StoreStaffRequest::staffAttributes() strips them server-side.
         *
         * Simulated by revoking the ability while keeping staff.manage, since
         * today only Admins hold either.
         */
        $admin = $this->admin();
        $member = User::factory()->employee()->create(['lead_module_access' => false]);

        \Gate::define('staff.settings.manage', fn () => false);

        $this->actingAs($admin)
            ->put(route('staff.update', $member), $this->payload([
                'email' => $member->email,
                'password' => '',
                'password_confirmation' => '',
                'lead_module_access' => '1',
            ]))
            ->assertSessionHasNoErrors();

        // The rest of the update applied; the privileged toggle did not.
        $this->assertFalse($member->fresh()->lead_module_access);
        $this->assertSame('Priya Nair', $member->fresh()->name);
    }

    #[Test]
    public function the_toggles_render_disabled_without_the_settings_ability(): void
    {
        \Gate::define('staff.settings.manage', fn () => false);

        $html = $this->actingAs($this->admin())
            ->get(route('staff.create'))
            ->getContent();

        $this->assertStringContainsString('Only an Admin can change this.', $html);
    }

    /*
    |--------------------------------------------------------------------------
    | Lead module access
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function admins_and_managers_reach_leads_on_their_role(): void
    {
        $this->assertTrue($this->admin()->canAccessLeadModule());
        $this->assertTrue(User::factory()->manager()->create()->canAccessLeadModule());
    }

    #[Test]
    public function an_employee_reaches_leads_only_with_the_flag(): void
    {
        $this->assertFalse(User::factory()->employee()->create()->canAccessLeadModule());
        $this->assertTrue(User::factory()->employee()->withLeadAccess()->create()->canAccessLeadModule());
    }

    #[Test]
    public function a_suspended_employee_never_reaches_leads(): void
    {
        $member = User::factory()->employee()->withLeadAccess()->suspended()->create();

        $this->assertFalse($member->canAccessLeadModule());
        $this->assertFalse($member->can('leads.access'));
    }

    #[Test]
    public function the_lead_menu_is_hidden_without_access(): void
    {
        \Gate::define('leads.access', fn () => false);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertDontSee('Lead Management');
    }

    #[Test]
    public function the_lead_menu_shows_with_access(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertSee('Lead Management');
    }

    #[Test]
    public function the_lead_route_is_hidden_rather_than_forbidden(): void
    {
        // 404 not 403: a 403 confirms the module exists.
        \Gate::define('leads.access', fn () => false);

        $this->actingAs($this->admin())
            ->get(route('leads.index'))
            ->assertNotFound();
    }

    #[Test]
    public function employees_cannot_delete_assign_or_reassign_leads(): void
    {
        $employee = User::factory()->employee()->withLeadAccess()->create();

        $this->assertTrue($employee->can('leads.access'));
        $this->assertTrue($employee->can('leads.create'));
        $this->assertFalse($employee->can('leads.delete'));
        $this->assertFalse($employee->can('leads.assign'));
        $this->assertFalse($employee->can('leads.changeOwner'));
    }

    #[Test]
    public function managers_retain_the_full_lead_abilities(): void
    {
        $manager = User::factory()->manager()->create();

        $this->assertTrue($manager->can('leads.delete'));
        $this->assertTrue($manager->can('leads.assign'));
        $this->assertTrue($manager->can('leads.changeOwner'));
    }

    /*
    |--------------------------------------------------------------------------
    | Reminder architecture
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_due_query_selects_only_staff_inside_the_window(): void
    {
        $due = User::factory()->employee()->incrementDueIn(3)->create(['name' => 'Due Soon']);
        $today = User::factory()->employee()->incrementDueIn(0)->create(['name' => 'Due Today']);
        $edge = User::factory()->employee()->incrementDueIn(5)->create(['name' => 'Edge Of Window']);
        $far = User::factory()->employee()->incrementDueIn(30)->create(['name' => 'Far Away']);
        $off = User::factory()->employee()->incrementDueIn(2)->create([
            'name' => 'Reminder Off', 'increment_notification' => false,
        ]);
        $suspended = User::factory()->employee()->incrementDueIn(2)->suspended()->create(['name' => 'Suspended']);
        User::factory()->employee()->create(['name' => 'No Date']);

        $ids = app(SalaryIncrementReminderService::class)->dueForReminder()->pluck('id');

        $this->assertTrue($ids->contains($due->id));
        $this->assertTrue($ids->contains($today->id));
        $this->assertTrue($ids->contains($edge->id), 'The window is inclusive of the fifth day.');
        $this->assertFalse($ids->contains($far->id));
        $this->assertFalse($ids->contains($off->id), 'Reminder disabled must be excluded.');
        $this->assertFalse($ids->contains($suspended->id), 'Suspended staff must be excluded.');
        $this->assertCount(3, $ids);
    }

    #[Test]
    public function only_admins_receive_the_reminder(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->employee()->create();
        User::factory()->employee()->incrementDueIn(2)->create();

        $count = app(SalaryIncrementReminderService::class)->sendReminders();

        $this->assertSame(1, $count);
        Notification::assertSentTo($admin, SalaryIncrementReminder::class);
        Notification::assertNotSentTo($manager, SalaryIncrementReminder::class);
        Notification::assertNotSentTo($employee, SalaryIncrementReminder::class);
    }

    #[Test]
    public function nothing_is_sent_when_no_increment_is_due(): void
    {
        Notification::fake();

        $this->admin();
        User::factory()->employee()->incrementDueIn(30)->create();

        $this->assertSame(0, app(SalaryIncrementReminderService::class)->sendReminders());
        Notification::assertNothingSent();
    }

    #[Test]
    public function the_reminder_payload_carries_every_field_the_brief_lists(): void
    {
        $employee = User::factory()->employee()->incrementDueIn(3)->create(['name' => 'John Doe']);

        $payload = (new SalaryIncrementReminder($employee))->toArray($this->admin());

        $this->assertSame('John Doe', $payload['employee_name']);
        $this->assertSame('25000.00', $payload['current_salary']);
        $this->assertSame('2500.00', $payload['increment_amount']);
        $this->assertSame('27500.00', $payload['projected_salary']);
        $this->assertSame(today()->addDays(3)->toDateString(), $payload['increment_date']);
    }

    #[Test]
    public function the_model_helper_agrees_with_the_query(): void
    {
        $this->assertTrue(User::factory()->employee()->incrementDueIn(3)->create()->isDueForIncrementReminder());
        $this->assertFalse(User::factory()->employee()->incrementDueIn(30)->create()->isDueForIncrementReminder());
        $this->assertFalse(User::factory()->employee()->create()->isDueForIncrementReminder());
    }

    /*
    |--------------------------------------------------------------------------
    | Listing & show
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_listing_shows_the_new_columns(): void
    {
        User::factory()->employee()->incrementDueIn(3)->withLeadAccess()->create(['name' => 'Priya Nair']);

        $this->actingAs($this->admin())
            ->get(route('staff.index'))
            ->assertOk()
            ->assertSee('Reminder')
            ->assertSee('Lead access')
            ->assertSee('Enabled')
            ->assertSee('ON');
    }

    #[Test]
    public function the_listing_can_be_sorted_by_increment_date(): void
    {
        $this->actingAs($this->admin())
            ->get(route('staff.index', ['sort' => 'increment_date', 'direction' => 'asc']))
            ->assertOk()
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function the_show_page_displays_the_salary_block(): void
    {
        $member = User::factory()->employee()->incrementDueIn(3)->create();

        $this->actingAs($this->admin())
            ->get(route('staff.show', $member))
            ->assertOk()
            ->assertSee('Current salary')
            ->assertSee('Increment amount')
            ->assertSee('Salary after increment')
            ->assertSee('27,500.00')
            ->assertSee('Due soon');
    }
}
