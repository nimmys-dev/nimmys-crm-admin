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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
     * Default payload for Answered -> Interested -> Not Sold
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'call_status' => CallStatus::Answered->value,
            'interest' => 1,
            'is_item_sold' => 0,
            'remarks' => '<p>Customer interested in product.</p>',
            'next_followup_date' => today()->addDays(5)->toDateString(),
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Create & validate Decision Tree
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function an_admin_can_log_an_answered_interested_not_sold_call(): void
    {
        $lead = Lead::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('leads.calls.store', $lead), $this->payload())
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();

        $call = CallDetail::first();

        $this->assertSame($lead->id, $call->lead_id);
        $this->assertSame(CallStatus::Answered, $call->call_status);
        $this->assertTrue($call->interest);
        $this->assertFalse($call->is_item_sold);
        $this->assertSame($admin->id, $call->called_by, 'Defaults to the acting user.');
        $this->assertTrue($call->called_date->isToday());
        $this->assertNotNull($call->next_followup_date);
    }

    #[Test]
    public function an_admin_can_log_a_not_answered_call(): void
    {
        $lead = Lead::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::NotAnswered->value,
                'called_date' => today()->toDateString(),
                'called_time' => '11:00',
                'next_followup_date' => today()->addDays(2)->toDateString(),
                'remarks' => 'Did not pick up.',
            ])
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();

        $call = CallDetail::first();

        $this->assertSame(CallStatus::NotAnswered, $call->call_status);
        $this->assertNull($call->interest);
        $this->assertNull($call->is_item_sold);
        $this->assertNotNull($call->next_followup_date);
        $this->assertSame('Did not pick up.', $call->remarks);
    }

    #[Test]
    public function an_admin_can_log_an_answered_not_interested_call_with_reason(): void
    {
        $lead = Lead::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::Answered->value,
                'interest' => 0,
                'reason' => 'Price is too high',
                'called_date' => today()->toDateString(),
                'called_time' => '14:00',
                'duration' => 60,
                'remarks' => 'Customer opted for another brand.',
            ])
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();

        $call = CallDetail::first();

        $this->assertSame(CallStatus::Answered, $call->call_status);
        $this->assertFalse($call->interest);
        $this->assertSame('Price is too high', $call->reason);
        $this->assertNull($call->is_item_sold);
    }

    #[Test]
    public function an_admin_can_log_an_answered_item_sold_call_with_invoice(): void
    {
        Storage::fake('public');

        $lead = Lead::factory()->create();
        $admin = $this->admin();
        $file = UploadedFile::fake()->create('invoice.pdf', 150, 'application/pdf');

        $this->actingAs($admin)
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::Answered->value,
                'interest' => 1,
                'is_item_sold' => 1,
                'invoice_number' => 'INV-2026-999',
                'invoice_file' => $file,
                'called_date' => today()->toDateString(),
                'called_time' => '15:30',
                'duration' => 320,
                'remarks' => 'Customer purchased Sony A7IV.',
            ])
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();

        $call = CallDetail::first();

        $this->assertSame(CallStatus::Answered, $call->call_status);
        $this->assertTrue($call->interest);
        $this->assertTrue($call->is_item_sold);
        $this->assertSame('INV-2026-999', $call->invoice_number);
        $this->assertNotNull($call->invoice_file_path);
        Storage::disk('public')->assertExists($call->invoice_file_path);
    }

    #[Test]
    public function required_fields_are_enforced(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [])
            ->assertSessionHasErrors(['call_status', 'remarks']);
    }

    #[Test]
    public function call_date_time_and_caller_are_automatically_populated(): void
    {
        $agent = $this->agent();
        $lead = Lead::factory()->assignedTo($agent->id)->create();

        $this->actingAs($agent)
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::NotAnswered->value,
                'next_followup_date' => today()->addDays(2)->toDateString(),
                'remarks' => 'Ring only',
            ])
            ->assertSessionHasNoErrors();

        $call = CallDetail::first();
        $this->assertSame($agent->id, $call->called_by);
        $this->assertTrue($call->called_date->isToday());
        $this->assertNotNull($call->called_time);
    }

    #[Test]
    public function not_answered_requires_next_followup_date(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::NotAnswered->value,
                'called_date' => today()->toDateString(),
                'called_time' => '10:00',
                'remarks' => 'No answer',
            ])
            ->assertSessionHasErrors('next_followup_date');
    }

    #[Test]
    public function answered_requires_interest(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::Answered->value,
                'called_date' => today()->toDateString(),
                'called_time' => '10:00',
                'remarks' => 'Spoke with customer',
            ])
            ->assertSessionHasErrors('interest');
    }

    #[Test]
    public function answered_not_interested_requires_reason(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::Answered->value,
                'interest' => 0,
                'called_date' => today()->toDateString(),
                'called_time' => '10:00',
                'remarks' => 'Not interested',
            ])
            ->assertSessionHasErrors('reason');
    }

    #[Test]
    public function answered_interested_requires_is_item_sold(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::Answered->value,
                'interest' => 1,
                'called_date' => today()->toDateString(),
                'called_time' => '10:00',
                'remarks' => 'Interested in camera',
            ])
            ->assertSessionHasErrors('is_item_sold');
    }

    #[Test]
    public function answered_interested_item_sold_requires_invoice_number_and_file(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::Answered->value,
                'interest' => 1,
                'is_item_sold' => 1,
                'called_date' => today()->toDateString(),
                'called_time' => '10:00',
                'remarks' => 'Sold',
            ])
            ->assertSessionHasErrors(['invoice_number', 'invoice_file']);
    }

    #[Test]
    public function answered_interested_not_sold_requires_next_followup_date(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::Answered->value,
                'interest' => 1,
                'is_item_sold' => 0,
                'called_date' => today()->toDateString(),
                'called_time' => '10:00',
                'remarks' => 'Considering',
            ])
            ->assertSessionHasErrors('next_followup_date');
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
    public function a_not_answered_call_cannot_have_a_duration(): void
    {
        $lead = Lead::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('leads.calls.store', $lead), [
                'call_status' => CallStatus::NotAnswered->value,
                'called_date' => today()->toDateString(),
                'called_time' => '10:00',
                'next_followup_date' => today()->addDays(2)->toDateString(),
                'remarks' => 'No answer',
                'duration' => 300,
            ])
            ->assertSessionHasErrors('duration');
    }

    #[Test]
    public function remarks_are_sanitised(): void
    {
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
    public function an_answered_call_advances_the_leads_last_contacted_at(): void
    {
        $lead = Lead::factory()->create(['last_contacted_at' => null]);

        $this->actingAs($this->admin())->post(route('leads.calls.store', $lead), $this->payload());

        $this->assertNotNull($lead->fresh()->last_contacted_at);
    }

    #[Test]
    public function a_not_answered_call_leaves_last_contacted_at_alone(): void
    {
        $lead = Lead::factory()->create(['last_contacted_at' => null]);

        $this->actingAs($this->admin())->post(route('leads.calls.store', $lead), [
            'call_status' => CallStatus::NotAnswered->value,
            'called_date' => today()->toDateString(),
            'called_time' => '10:00',
            'next_followup_date' => today()->addDays(2)->toDateString(),
            'remarks' => 'Ring only',
        ]);

        $this->assertNull($lead->fresh()->last_contacted_at, 'A call that was not answered is not contact.');
    }

    #[Test]
    public function logging_an_older_call_does_not_drag_last_contacted_backwards(): void
    {
        $recent = now()->subDay();
        $lead = Lead::factory()->create(['last_contacted_at' => $recent]);

        $this->actingAs($this->admin())->post(route('leads.calls.store', $lead), $this->payload([
            'called_date' => today()->subDays(10)->toDateString(),
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
        $this->assertNull(CallDetail::factory()->create(['next_followup_date' => null])->followUpUrgency());
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
        $manager = User::factory()->manager()->create();
        Shop::factory()->create(['manager_id' => $manager->id]);

        $call = CallDetail::factory()->for(Lead::factory()->create(['shop_id' => null]))->create();

        $this->assertTrue($manager->fresh()->can('view', $call));
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
                'remarks' => 'Updated remarks content.',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Updated remarks content.', $call->fresh()->remarks);

        $response = $this->actingAs($this->admin())->delete(route('leads.calls.destroy', [$lead, $call]));
        $response->assertRedirect(route('leads.show', $lead));

        $this->assertSoftDeleted($call);
    }

    #[Test]
    public function a_call_cannot_be_reached_through_the_wrong_lead(): void
    {
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
    public function the_history_can_be_searched_by_remarks_reason_or_invoice(): void
    {
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->create(['remarks' => 'Discussed pricing']);
        CallDetail::factory()->for($lead)->notInterested('Competitor cheaper')->create(['remarks' => 'Follow up note']);
        CallDetail::factory()->for($lead)->sold('INV-7777')->create(['remarks' => 'Delivered']);

        $foundRemarks = $this->service()->paginateForLead($lead, ['q' => 'pricing'])->pluck('remarks');
        $foundReason = $this->service()->paginateForLead($lead, ['q' => 'Competitor'])->pluck('reason');
        $foundInvoice = $this->service()->paginateForLead($lead, ['q' => '7777'])->pluck('invoice_number');

        $this->assertSame(['Discussed pricing'], $foundRemarks->all());
        $this->assertSame(['Competitor cheaper'], $foundReason->all());
        $this->assertSame(['INV-7777'], $foundInvoice->all());
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
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->count(15)->create();

        $page = $this->service()->paginateForLead($lead);

        $this->assertSame('call_page', $page->getPageName());
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
        $lead = Lead::factory()->create();
        CallDetail::factory()->for($lead)->count(3)->create([
            'interest' => true,
            'is_item_sold' => false,
        ]);

        $other = Lead::factory()->create();
        CallDetail::factory()->for($other)->sold()->create();

        $admin = $this->admin();

        $this->assertSame(2, $this->service()->interestedLeads($admin));
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
