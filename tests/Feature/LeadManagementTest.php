<?php

namespace Tests\Feature;

use App\Contracts\LeadRepository;
use App\Enums\FollowUpType;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\Shop;
use App\Models\User;
use App\Services\LeadService;
use App\Support\HtmlSanitiser;
use App\Support\LeadReference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** An Employee who may use the Lead module. */
    private function agent(): User
    {
        return User::factory()->employee()->withLeadAccess()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Anita Menon',
            'company' => 'Menon Traders',
            'email' => 'anita@example.com',
            'phone' => '9876543210',
            'city' => 'Kochi',
            'source' => 'referral',
            'status' => LeadStatus::New->value,
            'priority' => LeadPriority::Medium->value,
            'value' => '50000.00',
            'description' => '<p>Wants a quote.</p>',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Module access
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('leads.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function admins_and_managers_reach_the_module(): void
    {
        $this->actingAs($this->admin())->get(route('leads.index'))->assertOk();
        $this->actingAs(User::factory()->manager()->create())->get(route('leads.index'))->assertOk();
    }

    #[Test]
    public function an_employee_without_lead_access_is_ejected_from_the_web(): void
    {
        // web.access removes Employees from the portal before the module gate.
        $this->actingAs(User::factory()->employee()->create())
            ->get(route('leads.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function revoking_lead_access_hides_the_module_entirely(): void
    {
        // 404 not 403: a 403 would confirm the module exists.
        \Gate::define('leads.access', fn () => false);

        $this->actingAs($this->admin())->get(route('leads.index'))->assertNotFound();
        $this->actingAs($this->admin())->get(route('leads.create'))->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Ownership scoping — the core rule
    |--------------------------------------------------------------------------
    |
    | Asserted against the repository and policy rather than over HTTP, because
    | `web.access` ejects Employees from the portal before any lead route is
    | reached — they are mobile-only. These are the exact layers the future
    | mobile API will consume, so this is where the rule has to hold.
    */

    #[Test]
    public function an_employee_is_never_admitted_to_the_web_module(): void
    {
        $agent = $this->agent();
        $lead = Lead::factory()->assignedTo($agent->id)->create();

        $this->actingAs($agent)->get(route('leads.index'))->assertRedirect(route('login'));
        $this->actingAs($agent)->get(route('leads.show', $lead))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[Test]
    public function the_repository_shows_an_employee_only_their_own_leads(): void
    {
        $agent = $this->agent();
        Lead::factory()->assignedTo($agent->id)->create(['name' => 'Mine Alice']);
        Lead::factory()->assignedTo($this->agent()->id)->create(['name' => 'Theirs Bob']);
        Lead::factory()->create(['name' => 'Unassigned Carol']);

        $visible = app(LeadRepository::class)->paginate($agent, [])->pluck('name');

        $this->assertSame(['Mine Alice'], $visible->all());
    }

    #[Test]
    public function the_repository_shows_a_manager_the_whole_pipeline(): void
    {
        Lead::factory()->assignedTo($this->agent()->id)->create();
        Lead::factory()->count(2)->create();

        $visible = app(LeadRepository::class)->paginate(User::factory()->manager()->create(), []);

        $this->assertCount(3, $visible);
    }

    #[Test]
    public function scoping_survives_any_filter_or_sort(): void
    {
        // Scoping is applied before filters, so no query string can widen it.
        $agent = $this->agent();
        Lead::factory()->assignedTo($agent->id)->create(['name' => 'Mine Alice']);
        Lead::factory()->assignedTo($this->agent()->id)->create(['name' => 'Theirs Bob']);

        $visible = app(LeadRepository::class)->paginate($agent, [
            'sort' => 'name',
            'direction' => 'asc',
            'status' => LeadStatus::New->value,
        ])->pluck('name');

        $this->assertSame(['Mine Alice'], $visible->all());
    }

    #[Test]
    public function an_employee_may_view_and_update_only_their_own_lead(): void
    {
        $agent = $this->agent();
        $mine = Lead::factory()->assignedTo($agent->id)->create();
        $theirs = Lead::factory()->assignedTo($this->agent()->id)->create();

        $this->assertTrue($agent->can('view', $mine));
        $this->assertTrue($agent->can('update', $mine));

        $this->assertFalse($agent->can('view', $theirs));
        $this->assertFalse($agent->can('update', $theirs));
    }

    #[Test]
    public function an_unassigned_lead_belongs_to_no_employee(): void
    {
        $lead = Lead::factory()->create(['assigned_to' => null]);

        $this->assertFalse($this->agent()->can('view', $lead));
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions on destructive actions
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function an_employee_cannot_delete_or_reassign_even_their_own_lead(): void
    {
        $agent = $this->agent();
        $mine = Lead::factory()->assignedTo($agent->id)->create();

        $this->assertFalse($agent->can('delete', $mine), 'Employees must not erase pipeline history.');
        $this->assertFalse($agent->can('assign', $mine), 'Employees must not move ownership.');
    }

    #[Test]
    public function a_manager_holds_the_destructive_abilities(): void
    {
        $manager = User::factory()->manager()->create();
        $lead = Lead::factory()->create();

        $this->assertTrue($manager->can('delete', $lead));
        $this->assertTrue($manager->can('assign', $lead));
    }

    #[Test]
    public function a_lead_created_by_an_employee_is_owned_by_them(): void
    {
        /*
         * Leaving it unassigned would make the lead invisible to its own
         * creator the moment it was saved.
         */
        $agent = $this->agent();

        $lead = app(LeadService::class)->create($this->payload(), $agent);

        $this->assertSame($agent->id, $lead->assigned_to);
        $this->assertSame($agent->id, $lead->created_by);
    }

    #[Test]
    public function an_employee_cannot_hand_a_lead_to_someone_else_on_create(): void
    {
        // assigned_to is stripped for anyone without leads.assign, so the
        // service never sees it and falls back to the creator.
        $agent = $this->agent();
        $victim = $this->agent();

        $lead = app(LeadService::class)->create($this->payload(), $agent);

        $this->assertNotSame($victim->id, $lead->assigned_to);
        $this->assertSame($agent->id, $lead->assigned_to);
    }

    #[Test]
    public function an_admin_keeps_the_owner_they_chose(): void
    {
        $agent = $this->agent();

        $lead = app(LeadService::class)
            ->create($this->payload(['assigned_to' => $agent->id]), $this->admin());

        $this->assertSame($agent->id, $lead->assigned_to);
    }

    #[Test]
    public function an_admin_can_delete_and_reassign(): void
    {
        $lead = Lead::factory()->create();
        $agent = $this->agent();

        $this->actingAs($this->admin())
            ->put(route('leads.assignment.update', $lead), ['assigned_to' => $agent->id]);
        $this->assertSame($agent->id, $lead->fresh()->assigned_to);

        $this->actingAs($this->admin())->delete(route('leads.destroy', $lead));
        $this->assertSoftDeleted($lead);
    }

    #[Test]
    public function a_lead_cannot_be_assigned_to_someone_without_lead_access(): void
    {
        $lead = Lead::factory()->create();
        $noAccess = User::factory()->employee()->create();

        $this->actingAs($this->admin())
            ->put(route('leads.assignment.update', $lead), ['assigned_to' => $noAccess->id])
            ->assertSessionHasErrors('assigned_to');
    }

    /*
    |--------------------------------------------------------------------------
    | Create, validate, reference
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function an_admin_can_create_a_lead(): void
    {
        $shop = Shop::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.store'), $this->payload(['shop_id' => $shop->id]))
            ->assertSessionHasNoErrors();

        $lead = Lead::firstWhere('email', 'anita@example.com');

        $this->assertNotNull($lead);
        $this->assertSame('LEAD-0001', $lead->reference);
        $this->assertSame(LeadStatus::New, $lead->status);
        $this->assertSame($shop->id, $lead->shop_id);
    }

    #[Test]
    public function references_increment_and_are_never_reused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('leads.store'), $this->payload(['email' => 'a@example.com']));
        Lead::firstWhere('email', 'a@example.com')->delete();
        $this->actingAs($admin)->post(route('leads.store'), $this->payload(['email' => 'b@example.com']));

        $this->assertSame('LEAD-0002', Lead::firstWhere('email', 'b@example.com')->reference);
    }

    #[Test]
    public function the_reference_sequence_survives_rollover(): void
    {
        Lead::factory()->create(['reference' => LeadReference::format(9)]);

        $this->assertSame('LEAD-0010', LeadReference::next());
    }

    #[Test]
    public function required_fields_are_enforced(): void
    {
        $this->actingAs($this->admin())
            ->post(route('leads.store'), [])
            ->assertSessionHasErrors(['name', 'phone', 'status', 'priority']);
    }

    #[Test]
    public function losing_a_lead_requires_a_reason(): void
    {
        $this->actingAs($this->admin())
            ->post(route('leads.store'), $this->payload(['status' => LeadStatus::Lost->value]))
            ->assertSessionHasErrors('lost_reason');
    }

    #[Test]
    public function closing_a_lead_stamps_closed_at_and_reopening_clears_it(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('leads.store'), $this->payload([
            'status' => LeadStatus::Won->value,
        ]));

        $lead = Lead::firstWhere('email', 'anita@example.com');
        $this->assertNotNull($lead->closed_at);

        $this->actingAs($admin)->put(route('leads.update', $lead), $this->payload([
            'status' => LeadStatus::Negotiation->value,
        ]));

        $lead->refresh();
        $this->assertNull($lead->closed_at, 'Reopening must clear the closure stamp.');
        $this->assertNull($lead->lost_reason);
    }

    /*
    |--------------------------------------------------------------------------
    | Rich text safety
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function script_tags_are_stripped_from_the_description(): void
    {
        // The description is rendered unescaped, so it must be safe on entry.
        $this->actingAs($this->admin())->post(route('leads.store'), $this->payload([
            'description' => '<p>Hello</p><script>alert(1)</script>',
        ]));

        $lead = Lead::firstWhere('email', 'anita@example.com');

        $this->assertStringNotContainsString('<script', $lead->description);
        $this->assertStringContainsString('<p>Hello</p>', $lead->description);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function hostileMarkup(): array
    {
        return [
            'double quoted handler' => ['<p onerror="alert(1)">hi</p>', 'onerror'],
            'single quoted handler' => ["<p onclick='alert(1)'>hi</p>", 'onclick'],
            'unquoted handler' => ['<p onmouseover=alert(1)>hi</p>', 'onmouseover'],
            'uppercase handler' => ['<p ONERROR="alert(1)">hi</p>', 'ONERROR'],
            'hyphenated handler' => ['<p on-something="alert(1)">hi</p>', 'on-something'],
            'handler after another attr' => ['<a href="/x" onclick="alert(1)">hi</a>', 'onclick'],
            'javascript url' => ['<a href="javascript:alert(1)">click</a>', 'javascript:'],
            'spaced javascript url' => ['<a href="javascript : alert(1)">click</a>', 'javascript'],
            'data url' => ['<a href="data:text/html;base64,x">click</a>', 'data:'],
            'inline style' => ['<p style="width:expression(alert(1))">hi</p>', 'style='],
            'script tag' => ['<script>alert(1)</script><p>ok</p>', '<script'],
            'iframe' => ['<iframe src="//evil"></iframe><p>ok</p>', '<iframe'],
        ];
    }

    #[Test]
    #[DataProvider('hostileMarkup')]
    public function hostile_markup_is_stripped(string $input, string $mustNotSurvive): void
    {
        /*
         * The description is rendered unescaped, so anything that survives
         * here is stored XSS. A regex missed `onerror` once already — it
         * required two spaces — which is why the variants are enumerated.
         */
        $this->assertStringNotContainsStringIgnoringCase(
            $mustNotSurvive,
            (string) HtmlSanitiser::clean($input)
        );
    }

    #[Test]
    public function an_untouched_editor_stores_null_rather_than_an_empty_paragraph(): void
    {
        $this->assertNull(HtmlSanitiser::clean('<p><br></p>'));
        $this->assertNull(HtmlSanitiser::clean(''));
    }

    #[Test]
    public function safe_formatting_survives(): void
    {
        $clean = HtmlSanitiser::clean('<p><strong>Bold</strong> and <em>italic</em></p><ul><li>One</li></ul>');

        $this->assertStringContainsString('<strong>Bold</strong>', $clean);
        $this->assertStringContainsString('<li>One</li>', $clean);
    }

    /*
    |--------------------------------------------------------------------------
    | Follow-ups
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function scheduling_a_follow_up_sets_the_leads_next_date(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())->post(route('leads.follow-ups.store', $lead), [
            'type' => FollowUpType::Call->value,
            'scheduled_at' => now()->addDays(3)->format('Y-m-d H:i'),
            'notes' => 'Call back about pricing.',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($lead->fresh()->next_follow_up_at->isSameDay(now()->addDays(3)));
    }

    #[Test]
    public function the_next_date_tracks_the_earliest_open_follow_up(): void
    {
        $lead = Lead::factory()->create();
        $admin = $this->admin();

        foreach ([5, 2, 8] as $days) {
            $this->actingAs($admin)->post(route('leads.follow-ups.store', $lead), [
                'type' => FollowUpType::Call->value,
                'scheduled_at' => now()->addDays($days)->format('Y-m-d H:i'),
            ]);
        }

        $this->assertTrue($lead->fresh()->next_follow_up_at->isSameDay(now()->addDays(2)));
    }

    #[Test]
    public function completing_the_earliest_follow_up_advances_the_next_date(): void
    {
        $lead = Lead::factory()->create();
        $soon = LeadFollowUp::factory()->for($lead)->create(['scheduled_at' => now()->addDay()]);
        LeadFollowUp::factory()->for($lead)->create(['scheduled_at' => now()->addDays(6)]);

        app(LeadService::class)->syncNextFollowUp($lead);
        $this->assertTrue($lead->fresh()->next_follow_up_at->isSameDay(now()->addDay()));

        $this->actingAs($this->admin())
            ->patch(route('leads.follow-ups.complete', [$lead, $soon]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($soon->fresh()->isComplete());
        $this->assertTrue($lead->fresh()->next_follow_up_at->isSameDay(now()->addDays(6)));
    }

    #[Test]
    public function a_logged_follow_up_records_contact_and_leaves_no_open_date(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())->post(route('leads.follow-ups.store', $lead), [
            'type' => FollowUpType::Call->value,
            'log_now' => '1',
            'notes' => 'Spoke to them.',
        ])->assertSessionHasNoErrors();

        $lead->refresh();

        $this->assertNotNull($lead->last_contacted_at);
        $this->assertNull($lead->next_follow_up_at, 'A completed follow-up leaves nothing pending.');
    }

    #[Test]
    public function a_follow_up_must_be_scheduled_or_logged(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.follow-ups.store', $lead), ['type' => FollowUpType::Call->value])
            ->assertSessionHasErrors('scheduled_at');
    }

    #[Test]
    public function a_follow_up_cannot_be_moved_between_leads(): void
    {
        // The id is in the URL, so it must be checked against the parent.
        $lead = Lead::factory()->create();
        $otherFollowUp = LeadFollowUp::factory()->for(Lead::factory())->create();

        $this->actingAs($this->admin())
            ->patch(route('leads.follow-ups.complete', [$lead, $otherFollowUp]))
            ->assertNotFound();
    }

    #[Test]
    public function an_employee_may_log_follow_ups_on_their_own_lead(): void
    {
        // Through the service, as the mobile API will.
        $agent = $this->agent();
        $lead = Lead::factory()->assignedTo($agent->id)->create();

        $this->assertTrue($agent->can('addFollowUp', $lead));

        app(LeadService::class)->addFollowUp($lead, [
            'type' => FollowUpType::Call,
            'scheduled_at' => now()->addDay(),
        ], $agent);

        $this->assertSame(1, $lead->followUps()->count());
        $this->assertTrue($lead->fresh()->next_follow_up_at->isSameDay(now()->addDay()));
    }

    #[Test]
    public function an_employee_may_not_log_follow_ups_on_someone_elses_lead(): void
    {
        $lead = Lead::factory()->assignedTo($this->agent()->id)->create();

        $this->assertFalse($this->agent()->can('addFollowUp', $lead));
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_conversion_rate_measures_decided_leads_only(): void
    {
        /*
         * 3 won, 1 lost, 6 still open → 75%. Measuring against all 10 would
         * report 30% and make a healthy pipeline look like a failing one.
         */
        Lead::factory()->count(3)->status(LeadStatus::Won)->create();
        Lead::factory()->status(LeadStatus::Lost)->create();
        Lead::factory()->count(6)->create();

        $stats = app(LeadRepository::class)->statistics($this->admin());

        $this->assertSame(10, $stats['total']);
        $this->assertSame(6, $stats['open']);
        $this->assertSame(75.0, $stats['conversion_rate']);
    }

    #[Test]
    public function the_conversion_rate_is_null_before_anything_is_decided(): void
    {
        // Zero would read as "we lose everything", which is not what no data means.
        Lead::factory()->count(3)->create();

        $this->assertNull(app(LeadRepository::class)->statistics($this->admin())['conversion_rate']);
    }

    #[Test]
    public function statistics_are_scoped_to_the_viewer(): void
    {
        $agent = $this->agent();
        Lead::factory()->assignedTo($agent->id)->create();
        Lead::factory()->count(4)->create();

        $this->assertSame(1, app(LeadRepository::class)->statistics($agent)['total']);
        $this->assertSame(5, app(LeadRepository::class)->statistics($this->admin())['total']);
    }

    #[Test]
    public function overdue_leads_are_counted_separately_from_todays(): void
    {
        Lead::factory()->followUpDueIn(-3)->create();
        Lead::factory()->followUpDueIn(0)->create();
        Lead::factory()->followUpDueIn(5)->create();

        $stats = app(LeadRepository::class)->statistics($this->admin());

        $this->assertSame(1, $stats['overdue']);
        $this->assertSame(2, $stats['due_today'], 'Due today includes anything already overdue.');
    }

    #[Test]
    public function closed_leads_never_appear_as_due(): void
    {
        Lead::factory()->status(LeadStatus::Won)->followUpDueIn(-2)->create();

        $this->assertSame(0, app(LeadRepository::class)->statistics($this->admin())['overdue']);
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard integration
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_dashboard_shows_real_lead_statistics(): void
    {
        Lead::factory()->count(2)->status(LeadStatus::Won)->create();
        Lead::factory()->followUpDueIn(-1)->create(['name' => 'Chase Me']);

        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lead statistics')
            ->assertSee('Follow-ups due')
            ->assertSee('Chase Me')
            ->assertDontSee('Available once the Lead module is built.');
    }

    #[Test]
    public function the_task_placeholder_is_still_there(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard'))
            ->assertSee('Task statistics')
            ->assertSee('Available once the Task module is built.');
    }
}
