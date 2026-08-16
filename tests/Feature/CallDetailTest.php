<?php

namespace Tests\Feature;

use App\Enums\CallStatus;
use App\Enums\FollowUpUrgency;
use App\Models\CallDetail;
use App\Models\Lead;
use App\Models\Shop;
use App\Models\User;
use App\Services\CallDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CallDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function agent(): User
    {
        return User::factory()->employee()->withLeadAccess()->create();
    }

    private function service(): CallDetailService
    {
        return app(CallDetailService::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'call_status' => CallStatus::Connected->value,
            'remarks' => '<p>Customer interested in product.</p>',
            'called_date' => today()->toDateString(),
            'called_time' => '10:30',
            'next_followup_date' => today()->addDays(5)->toDateString(),
            'duration' => 155,
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Create & validate
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function an_admin_can_log_a_call(): void
    {
        $lead = Lead::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('leads.calls.store', $lead), $this->payload())
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();

        $call = CallDetail::first();

        $this->assertSame($lead->id, $call->lead_id);
        $this->assertSame(CallStatus::Connected, $call->call_status);
        $this->assertSame($admin->id, $call->called_by, 'Defaults to the acting user.');
        $this->assertSame(155, $call->duration);
    }

    #[Test]
    public function required_fields_are_enforced(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [])
            ->assertSessionHasErrors(['call_status', 'called_date', 'called_time']);
    }

    #[Test]
    public function a_call_cannot_be_dated_in_the_future(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), $this->payload([
                'called_date' => today()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('called_date');
    }

    #[Test]
    public function a_follow_up_cannot_precede_the_call(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), $this->payload([
                'called_date' => today()->toDateString(),
                'next_followup_date' => today()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('next_followup_date');
    }

    #[Test]
    public function a_terminal_outcome_cannot_carry_a_follow_up(): void
    {
        // Nothing left to follow up on a Converted or Lost call.
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), $this->payload([
                'call_status' => CallStatus::Converted->value,
                'next_followup_date' => today()->addDays(3)->toDateString(),
            ]))
            ->assertSessionHasErrors('next_followup_date');
    }

    #[Test]
    public function an_unconnected_call_cannot_have_a_duration(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), $this->payload([
                'call_status' => CallStatus::Busy->value,
                'next_followup_date' => null,
                'duration' => 300,
            ]))
            ->assertSessionHasErrors('duration');
    }

    #[Test]
    public function remarks_are_sanitised(): void
    {
        // Remarks are rendered unescaped in the timeline.
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())->post(route('leads.calls.store', $lead), $this->payload([
            'remarks' => '<p>Fine</p><script>alert(1)</script><p onerror="alert(2)">x</p>',
        ]));

        $remarks = CallDetail::first()->remarks;

        $this->assertStringNotContainsString('<script', $remarks);
        $this->assertStringNotContainsString('onerror', $remarks);
        $this->assertStringContainsString('<p>Fine</p>', $remarks);
    }

    /*
    |--------------------------------------------------------------------------
    | Lead side effects
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function a_connected_call_advances_the_leads_last_contacted_at(): void
    {
        $lead = Lead::factory()->create(['last_contacted_at' => null]);

        $this->actingAs($this->admin())->post(route('leads.calls.store', $lead), $this->payload());

        $this->assertNotNull($lead->fresh()->last_contacted_at);
    }

    #[Test]
    public function an_unconnected_call_leaves_last_contacted_at_alone(): void
    {
        $lead = Lead::factory()->create(['last_contacted_at' => null]);

        $this->actingAs($this->admin())->post(route('leads.calls.store', $lead), $this->payload([
            'call_status' => CallStatus::NotConnected->value,
            'next_followup_date' => null,
            'duration' => null,
        ]));

        $this->assertNull($lead->fresh()->last_contacted_at, 'A call that never reached anyone is not contact.');
    }

    #[Test]
    public function logging_an_older_call_does_not_drag_last_contacted_backwards(): void
    {
        $recent = now()->subDay();
        $lead = Lead::factory()->create(['last_contacted_at' => $recent]);

        $this->actingAs($this->admin())->post(route('leads.calls.store', $lead), $this->payload([
            'called_date' => today()->subDays(10)->toDateString(),
            'next_followup_date' => null,
        ]));

        $this->assertTrue(
            $lead->fresh()->last_contacted_at->isSameSecond($recent),
            'Back-filling an old call must not rewind the most recent contact.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Timeline
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_timeline_is_newest_first_including_same_day_calls(): void
    {
        // Ordering by date alone would shuffle calls made on the same day.
        $lead = Lead::factory()->create();

        CallDetail::factory()->for($lead)->madeOn(0, '09:00:00')->create(['remarks' => 'Morning']);
        CallDetail::factory()->for($lead)->madeOn(0, '16:00:00')->create(['remarks' => 'Afternoon']);
        CallDetail::factory()->for($lead)->madeOn(3, '12:00:00')->create(['remarks' => 'Three days ago']);

        $order = $this->service()->getLeadTimeline($lead)->pluck('remarks')->all();

        $this->assertSame(['Afternoon', 'Morning', 'Three days ago'], $order);
    }

    #[Test]
    public function the_lead_page_renders_the_timeline_and_history(): void
    {
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->create(['remarks' => 'Spoke about pricing']);

        $this->actingAs($this->admin())
            ->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Call history')
            ->assertSee('Spoke about pricing');
    }

    /*
    |--------------------------------------------------------------------------
    | Follow-up urgency
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, array{int, FollowUpUrgency}>
     */
    public static function urgencyCases(): array
    {
        return [
            'yesterday is overdue' => [-1, FollowUpUrgency::Overdue],
            'today is due today' => [0, FollowUpUrgency::DueToday],
            'tomorrow is upcoming' => [1, FollowUpUrgency::Upcoming],
            'next week is upcoming' => [7, FollowUpUrgency::Upcoming],
        ];
    }

    #[Test]
    #[DataProvider('urgencyCases')]
    public function follow_up_urgency_is_derived_from_the_date(int $days, FollowUpUrgency $expected): void
    {
        $call = CallDetail::factory()->followUpIn($days)->create();

        $this->assertSame($expected, $call->followUpUrgency());
    }

    #[Test]
    public function no_follow_up_date_means_no_urgency(): void
    {
        // Absence of a date is not an urgency level.
        $this->assertNull(CallDetail::factory()->create(['next_followup_date' => null])->followUpUrgency());
    }

    #[Test]
    public function upcoming_followups_are_scoped_and_ordered(): void
    {
        $agent = $this->agent();
        $mine = Lead::factory()->assignedTo($agent->id)->create();
        $theirs = Lead::factory()->assignedTo($this->agent()->id)->create();

        CallDetail::factory()->for($mine)->followUpIn(-2)->create(['remarks' => 'Overdue mine']);
        CallDetail::factory()->for($mine)->followUpIn(0)->create(['remarks' => 'Today mine']);
        CallDetail::factory()->for($mine)->followUpIn(9)->create(['remarks' => 'Later mine']);
        CallDetail::factory()->for($theirs)->followUpIn(0)->create(['remarks' => 'Today theirs']);

        $due = $this->service()->getUpcomingFollowups($agent)->pluck('remarks')->all();

        $this->assertSame(['Overdue mine', 'Today mine'], $due, 'Own leads only, soonest first.');
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function guests_cannot_log_calls(): void
    {
        $lead = Lead::factory()->create();

        $this->post(route('leads.calls.store', $lead), $this->payload())
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function an_employee_may_log_and_view_calls_on_their_own_lead(): void
    {
        $agent = $this->agent();
        $lead = Lead::factory()->assignedTo($agent->id)->create();

        $this->assertTrue($agent->can('create', [CallDetail::class, $lead]));

        $call = $this->service()->createCall($lead, $this->payload(), $agent);

        $this->assertTrue($agent->can('view', $call));
        $this->assertSame($agent->id, $call->called_by);
    }

    #[Test]
    public function an_employee_cannot_touch_calls_on_someone_elses_lead(): void
    {
        $lead = Lead::factory()->assignedTo($this->agent()->id)->create();
        $call = CallDetail::factory()->for($lead)->create();
        $intruder = $this->agent();

        $this->assertFalse($intruder->can('create', [CallDetail::class, $lead]));
        $this->assertFalse($intruder->can('view', $call));
        $this->assertFalse($intruder->can('update', $call));
        $this->assertFalse($intruder->can('delete', $call));
    }

    #[Test]
    public function an_employee_may_edit_only_their_own_entries(): void
    {
        /*
         * The history records who said what. Rewriting a colleague's note on
         * a shared lead would destroy that, so ownership of the entry — not
         * of the lead — governs editing.
         */
        $agent = $this->agent();
        $lead = Lead::factory()->assignedTo($agent->id)->create();

        $mine = CallDetail::factory()->for($lead)->by($agent->id)->create();
        $colleagues = CallDetail::factory()->for($lead)->by($this->admin()->id)->create();

        $this->assertTrue($agent->can('update', $mine));
        $this->assertFalse($agent->can('update', $colleagues));
    }

    #[Test]
    public function an_employee_never_deletes_call_history(): void
    {
        $agent = $this->agent();
        $lead = Lead::factory()->assignedTo($agent->id)->create();
        $mine = CallDetail::factory()->for($lead)->by($agent->id)->create();

        $this->assertFalse($agent->can('delete', $mine), 'Not even their own entry.');
    }

    #[Test]
    public function a_manager_is_limited_to_their_own_shop(): void
    {
        $manager = User::factory()->manager()->create();
        $shop = Shop::factory()->create(['manager_id' => $manager->id]);
        $otherShop = Shop::factory()->create();

        $mine = CallDetail::factory()->for(Lead::factory()->forShop($shop->id))->create();
        $theirs = CallDetail::factory()->for(Lead::factory()->forShop($otherShop->id))->create();

        $manager = $manager->fresh();

        $this->assertTrue($manager->can('view', $mine));
        $this->assertTrue($manager->can('delete', $mine));

        $this->assertFalse($manager->can('view', $theirs), 'Another shop is out of reach.');
        $this->assertFalse($manager->can('delete', $theirs));
    }

    #[Test]
    public function a_manager_can_still_reach_leads_with_no_shop(): void
    {
        // Otherwise unassigned leads would become permanently unmanageable.
        $manager = User::factory()->manager()->create();
        Shop::factory()->create(['manager_id' => $manager->id]);

        $call = CallDetail::factory()->for(Lead::factory()->create(['shop_id' => null]))->create();

        $this->assertTrue($manager->fresh()->can('view', $call));
    }

    #[Test]
    public function manager_shop_reach_survives_the_eager_loaded_lead(): void
    {
        /*
         * Regression: the eager load selected a narrow column list that
         * omitted shop_id. The policy read null, treated the lead as
         * unassigned, and handed every Manager access to every shop.
         *
         * Goes through the service so the real eager-load constraint runs —
         * building the model by hand would not reproduce it.
         */
        $manager = User::factory()->manager()->create();
        Shop::factory()->create(['manager_id' => $manager->id]);
        $otherShop = Shop::factory()->create();

        $foreign = Lead::factory()->forShop($otherShop->id)->create();
        CallDetail::factory()->for($foreign)->followUpIn(0)->create();

        $manager = $manager->fresh();

        foreach ($this->service()->getUpcomingFollowups($manager) as $call) {
            $this->assertNotNull($call->lead->shop_id, 'shop_id must survive the eager load.');
            $this->assertFalse($manager->can('view', $call), 'Another shop must stay out of reach.');
        }
    }

    #[Test]
    public function the_lead_page_renders_action_buttons_without_lazy_loading(): void
    {
        /*
         * Regression: @can('update', $call) reads $call->lead, so the parent
         * must be eager loaded or preventLazyLoading turns the page into a
         * 500. Caught live, not by the earlier render test.
         */
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Call history');
    }

    #[Test]
    public function an_admin_has_full_reach(): void
    {
        $admin = $this->admin();
        $call = CallDetail::factory()->for(Lead::factory()->forShop(Shop::factory()->create()->id))->create();

        $this->assertTrue($admin->can('view', $call));
        $this->assertTrue($admin->can('update', $call));
        $this->assertTrue($admin->can('delete', $call));
    }

    /*
    |--------------------------------------------------------------------------
    | Update & delete over HTTP
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function an_admin_can_update_and_delete_a_call(): void
    {
        $lead = Lead::factory()->create();
        $call = CallDetail::factory()->for($lead)->create();

        $this->actingAs($this->admin())
            ->put(route('leads.calls.update', [$lead, $call]), $this->payload([
                'call_status' => CallStatus::Interested->value,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(CallStatus::Interested, $call->fresh()->call_status);

        $this->actingAs($this->admin())->delete(route('leads.calls.destroy', [$lead, $call]));

        $this->assertSoftDeleted($call);
    }

    #[Test]
    public function a_call_cannot_be_reached_through_the_wrong_lead(): void
    {
        // The ids are in the URL, so the parent must be verified.
        $lead = Lead::factory()->create();
        $foreign = CallDetail::factory()->for(Lead::factory())->create();

        $this->actingAs($this->admin())
            ->get(route('leads.calls.show', [$lead, $foreign]))
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->delete(route('leads.calls.destroy', [$lead, $foreign]))
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Search & pagination
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_history_can_be_searched_by_remarks(): void
    {
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->create(['remarks' => 'Discussed pricing']);
        CallDetail::factory()->for($lead)->create(['remarks' => 'Left a voicemail']);

        $found = $this->service()->paginateForLead($lead, ['q' => 'pricing'])->pluck('remarks');

        $this->assertSame(['Discussed pricing'], $found->all());
    }

    #[Test]
    public function a_search_term_with_like_wildcards_is_escaped(): void
    {
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->create(['remarks' => 'Real remark']);

        $this->assertCount(0, $this->service()->paginateForLead($lead, ['q' => '%']));
    }

    #[Test]
    public function the_history_paginates_under_its_own_page_name(): void
    {
        // A distinct page name so it cannot collide with another paginator.
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->count(15)->create();

        $page = $this->service()->paginateForLead($lead);

        $this->assertSame('calls', $page->getPageName());
        $this->assertCount(CallDetailService::PER_PAGE, $page);
    }

    #[Test]
    public function an_unknown_sort_column_falls_back_instead_of_reaching_the_query(): void
    {
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->madeOn(2)->create(['remarks' => 'Older']);
        CallDetail::factory()->for($lead)->madeOn(0)->create(['remarks' => 'Newer']);

        $order = $this->service()
            ->paginateForLead($lead, ['sort' => 'password'])
            ->pluck('remarks')
            ->all();

        $this->assertSame(['Newer', 'Older'], $order, 'Falls back to newest first.');
    }

    /*
    |--------------------------------------------------------------------------
    | Prepared statistics
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_prepared_statistics_are_scoped_to_the_viewer(): void
    {
        $agent = $this->agent();
        $mine = Lead::factory()->assignedTo($agent->id)->create();
        $theirs = Lead::factory()->create();

        CallDetail::factory()->for($mine)->count(2)->create();
        CallDetail::factory()->for($theirs)->count(3)->create();

        $this->assertSame(2, $this->service()->totalCalls($agent));
        $this->assertSame(5, $this->service()->totalCalls($this->admin()));
    }

    #[Test]
    public function todays_calls_exclude_earlier_days(): void
    {
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->madeOn(0)->count(2)->create();
        CallDetail::factory()->for($lead)->madeOn(4)->create();

        $this->assertSame(2, $this->service()->todaysCalls($this->admin()));
    }

    #[Test]
    public function interested_and_converted_count_leads_not_calls(): void
    {
        /*
         * A lead called three times, all Interested, is one interested lead.
         * Counting calls would triple it and overstate the pipeline.
         */
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->status(CallStatus::Interested)->count(3)->create();

        $other = Lead::factory()->create();
        CallDetail::factory()->for($other)->status(CallStatus::Converted)->create();

        $admin = $this->admin();

        $this->assertSame(1, $this->service()->interestedLeads($admin));
        $this->assertSame(1, $this->service()->convertedLeads($admin));
    }

    #[Test]
    public function pending_followups_counts_today_and_overdue(): void
    {
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->followUpIn(-3)->create();
        CallDetail::factory()->for($lead)->followUpIn(0)->create();
        CallDetail::factory()->for($lead)->followUpIn(6)->create();

        $this->assertSame(2, $this->service()->pendingFollowups($this->admin()));
    }

    #[Test]
    public function every_prepared_figure_is_available_in_one_call(): void
    {
        // The dashboard widget is not built; these are the figures it will ask
        // for, so wiring it up later is a view change only.
        $keys = array_keys($this->service()->statistics($this->admin()));

        $this->assertSame([
            'total_calls',
            'todays_calls',
            'interested_leads',
            'converted_leads',
            'pending_followups',
        ], $keys);
    }
}
