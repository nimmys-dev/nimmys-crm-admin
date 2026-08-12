<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Http\Requests\Lead\CloseLeadRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;

/**
 * Closing a lead as Won or Lost from the detail page.
 *
 * Its own controller for the same reason as LeadAssignmentController: a
 * distinct, narrower action than the full lead edit form, with its own
 * request and its own audit-worthy event.
 */
class LeadCloseController extends Controller
{
    public function __construct(private readonly LeadService $service) {}

    public function update(CloseLeadRequest $request, Lead $lead): RedirectResponse
    {
        $status = LeadStatus::from($request->validated('status'));

        $this->service->close($lead, $status, $request->validated('lost_reason'));

        return back()->with('success', "Lead {$lead->reference} was marked {$status->label()}.");
    }
}
