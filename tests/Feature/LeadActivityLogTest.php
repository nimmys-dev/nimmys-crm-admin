<?php

namespace Tests\Feature;

use App\Enums\CallStatus;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Shop;
use App\Models\User;
use App\Services\CallDetailService;
use App\Services\LeadActivityService;
use App\Services\LeadService;
use App\Services\QuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadActivityLogTest extends TestCase
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

    private function leadService(): LeadService
    {
        return app(LeadService::class);
    }

    private function callService(): CallDetailService
    {
        return app(CallDetailService::class);
    }

    private function quotationService(): QuotationService
    {
        return app(QuotationService::class);
    }

    private function activityService(): LeadActivityService
    {
        return app(LeadActivityService::class);
    }

    #[Test]
    public function creating_a_lead_records_created_activity(): void
    {
        $admin = $this->admin();
        $shop = Shop::factory()->create();

        $lead = $this->leadService()->create([
            'name' => 'Dr. Robert Oppenheimer',
            'phone' => '9876543210',
            'email' => 'oppenheimer@example.com',
            'shop_id' => $shop->id,
            'source' => \App\Enums\LeadSource::Website->value,
            'status' => LeadStatus::New->value,
        ], $admin);

        $activities = $this->activityService()->getActivitiesForLead($lead);

        $this->assertCount(1, $activities);
        $activity = $activities->first();
        $this->assertSame('created', $activity->activity_type);
        $this->assertSame($admin->id, $activity->user_id);
        $this->assertStringContainsString('Lead created by', $activity->description);
    }

    #[Test]
    public function updating_lead_records_updated_activity(): void
    {
        $admin = $this->admin();
        $lead = Lead::factory()->create(['name' => 'Original Name', 'phone' => '1111111111']);

        $this->leadService()->update($lead, [
            'name' => 'Updated Name',
            'phone' => '9999999999',
        ], $admin);

        $activities = $this->activityService()->getActivitiesForLead($lead);

        $this->assertTrue($activities->contains('activity_type', 'updated'));
        $updateActivity = $activities->firstWhere('activity_type', 'updated');
        $this->assertStringContainsString('Updated lead details', $updateActivity->description);
    }

    #[Test]
    public function reassigning_lead_records_reassigned_activity(): void
    {
        $admin = $this->admin();
        $agent1 = $this->agent();
        $agent2 = $this->agent();

        $lead = Lead::factory()->assignedTo($agent1->id)->create();

        $this->leadService()->assign($lead, $agent2->id, $admin);

        $activities = $this->activityService()->getActivitiesForLead($lead);
        $assignActivity = $activities->firstWhere('activity_type', 'reassigned');

        $this->assertNotNull($assignActivity);
        $this->assertSame($admin->id, $assignActivity->user_id);
        $this->assertStringContainsString("Reassigned lead from {$agent1->name} to {$agent2->name}", $assignActivity->description);
    }

    #[Test]
    public function closing_lead_records_closed_activity_with_reason(): void
    {
        $admin = $this->admin();
        $lead = Lead::factory()->create();

        $this->leadService()->close($lead, LeadStatus::Lost, 'Competitor offer was better', $admin);

        $activities = $this->activityService()->getActivitiesForLead($lead);
        $closedActivity = $activities->firstWhere('activity_type', 'closed');

        $this->assertNotNull($closedActivity);
        $this->assertStringContainsString('Competitor offer was better', $closedActivity->description);
    }

    #[Test]
    public function logging_a_call_records_call_activity(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $lead = Lead::factory()->create();
        $file = UploadedFile::fake()->create('inv.pdf', 100, 'application/pdf');

        $call = $this->callService()->createCall($lead, [
            'call_status' => CallStatus::Answered->value,
            'interest' => true,
            'is_item_sold' => true,
            'invoice_number' => 'INV-2026-ACT-1',
            'remarks' => 'Sold Sony FX3 camera.',
        ], $admin, $file);

        $activities = $this->activityService()->getActivitiesForLead($lead);
        $callActivity = $activities->firstWhere('activity_type', 'call_logged');

        $this->assertNotNull($callActivity);
        $this->assertStringContainsString('INV-2026-ACT-1', $callActivity->description);
        $this->assertSame('INV-2026-ACT-1', $callActivity->properties['invoice_number']);
    }

    #[Test]
    public function creating_and_updating_quotation_records_quotation_activities(): void
    {
        $admin = $this->admin();
        $lead = Lead::factory()->create();

        $quotation = $this->quotationService()->create($lead, [
            'customer_name' => 'Visakh',
            'customer_address' => 'Kottayam',
            'issue_date' => '2026-08-18',
            'discount_percent' => 0,
            'tax_percent' => 18,
        ], [
            ['description' => 'Sony Lens 24-70mm', 'quantity' => 1, 'rate' => 150000, 'tax_percent' => 18],
        ], $admin);

        $activities = $this->activityService()->getActivitiesForLead($lead);
        $quotationActivity = $activities->firstWhere('activity_type', 'quotation_created');

        $this->assertNotNull($quotationActivity);
        $this->assertStringContainsString('Created Quotation', $quotationActivity->description);

        // Update quotation
        $this->quotationService()->update($quotation, [
            'discount_percent' => 5,
        ], [
            ['description' => 'Sony Lens 24-70mm', 'quantity' => 2, 'rate' => 150000, 'tax_percent' => 18],
        ]);

        $updatedActivities = $this->activityService()->getActivitiesForLead($lead);
        $this->assertTrue($updatedActivities->contains('activity_type', 'quotation_updated'));
    }

    #[Test]
    public function lead_show_page_renders_activity_log_button_and_modal(): void
    {
        $admin = $this->admin();
        $lead = Lead::factory()->create();

        $this->actingAs($admin)
            ->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Activity Log')
            ->assertSee('activityLogModal');
    }
}
