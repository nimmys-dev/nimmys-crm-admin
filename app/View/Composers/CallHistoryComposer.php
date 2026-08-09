<?php

namespace App\View\Composers;

use App\Enums\CallStatus;
use App\Http\Requests\CallDetail\CallHistoryRequest;
use App\Http\Requests\CallDetail\StoreCallDetailRequest;
use App\Services\CallDetailService;
use Illuminate\View\View;

/**
 * Injects call history into the Lead detail view.
 *
 * A composer rather than a change to LeadController: the Call Details module
 * is a child that plugs into the Lead page, and it should be able to do that
 * without the Lead module knowing it exists. Deleting this module would mean
 * removing the composer registration and the two @include lines — nothing in
 * the Lead module itself.
 */
class CallHistoryComposer
{
    public function __construct(private readonly CallDetailService $calls) {}

    public function compose(View $view): void
    {
        $lead = $view->getData()['lead'] ?? null;

        if ($lead === null) {
            return;
        }

        $request = app(CallHistoryRequest::class);
        $filters = $request->filters();

        $view->with([
            'callHistory' => $this->calls->paginateForLead($lead, $filters),
            'callTimeline' => $this->calls->getLeadTimeline($lead),
            'callFilters' => $filters,
            'callHasActiveFilters' => $request->hasActiveFilters(),
            'callStatusOptions' => CallStatus::options(),
            'callerOptions' => auth()->user()?->can('leads.manage')
                ? StoreCallDetailRequest::callerOptions()
                : [],
            'canAttributeCall' => auth()->user()?->can('leads.manage') ?? false,
        ]);
    }
}
