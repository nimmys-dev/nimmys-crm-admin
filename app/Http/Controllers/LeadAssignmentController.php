<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lead\AssignLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;

/**
 * Reassigning a lead's owner.
 *
 * Its own controller because assignment is a distinct permission
 * (leads.assign) from editing a lead, and a distinct business event — an
 * Employee may edit their own lead but must never be able to hand it to
 * someone else, or take someone else's.
 */
class LeadAssignmentController extends Controller
{
    public function __construct(private readonly LeadService $service) {}

    public function update(AssignLeadRequest $request, Lead $lead): RedirectResponse
    {
        $userId = $request->validated('assigned_to');

        $this->service->assign($lead, $userId ? (int) $userId : null);

        $owner = $userId ? User::find($userId)?->name : null;

        return back()->with(
            'success',
            $owner
                ? "Lead {$lead->reference} was assigned to {$owner}."
                : "Lead {$lead->reference} is now unassigned."
        );
    }
}
