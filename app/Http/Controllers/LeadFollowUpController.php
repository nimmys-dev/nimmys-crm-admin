<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lead\StoreFollowUpRequest;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Follow-up tracking against a lead.
 *
 * Nested under the lead because a follow-up has no standalone meaning, and
 * authorised through the lead so ownership rules are inherited rather than
 * restated.
 */
class LeadFollowUpController extends Controller
{
    public function __construct(private readonly LeadService $service) {}

    public function store(StoreFollowUpRequest $request, Lead $lead): RedirectResponse
    {
        $followUp = $this->service->addFollowUp(
            $lead,
            $request->followUpAttributes(),
            $request->user(),
        );

        return back()->with(
            'success',
            $followUp->isComplete()
                ? 'Follow-up logged.'
                : 'Follow-up scheduled for '.$followUp->scheduled_at->format('j M Y').'.'
        );
    }

    /**
     * Mark an open follow-up as done.
     */
    public function complete(Request $request, Lead $lead, LeadFollowUp $followUp): RedirectResponse
    {
        // Authorised against the parent lead, and the follow-up must belong
        // to it — otherwise the id could be swapped for another lead's row.
        $this->authorize('addFollowUp', $lead);
        abort_unless($followUp->lead_id === $lead->id, 404);

        $this->service->completeFollowUp($followUp, $request->string('outcome')->trim()->value() ?: null);

        return back()->with('success', 'Follow-up marked as complete.');
    }
}
