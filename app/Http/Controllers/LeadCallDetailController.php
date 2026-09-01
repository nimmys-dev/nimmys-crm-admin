<?php

namespace App\Http\Controllers;

use App\Enums\CallStatus;
use App\Http\Requests\CallDetail\StoreCallDetailRequest;
use App\Http\Requests\CallDetail\UpdateCallDetailRequest;
use App\Models\CallDetail;
use App\Models\Lead;
use App\Services\CallDetailService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Enums\LeadStatus;

/**
 * Call details, nested under a lead.
 *
 * Every action is authorised through CallDetailPolicy, which defers to
 * LeadPolicy first — so a user who cannot reach the lead can never reach its
 * calls. Listing lives on the Lead detail page, injected by
 * CallHistoryComposer, which is why there is no index() here.
 */
class LeadCallDetailController extends Controller
{
    public function __construct(private readonly CallDetailService $calls) {}

   public function store(StoreCallDetailRequest $request, Lead $lead): RedirectResponse
{
    // Get values BEFORE creating call
    $callStatus = strtolower((string) (
        $request->input('call_status')
        ?? $request->input('call_details')
    ));

    $interest = $request->input('interest');

    $isItemSold = $request->input('is_item_sold');

    /*
    |--------------------------------------------------------------------------
    | Answered + Interest = false
    | Reason + Remarks
    | Lead -> Closed
    |--------------------------------------------------------------------------
    */
    if (
        $callStatus === 'answered' &&
        $interest === false
    ) {
        $lead->update([
            'status' => LeadStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Answered + Interest = true + Item Sold = yes
    | Invoice + Remarks
    | Lead -> Closed
    |--------------------------------------------------------------------------
    */
    elseif (
        $callStatus === 'answered' &&
        $interest === true &&
        in_array(
            strtolower((string) $isItemSold),
            ['yes', '1', 'true'],
            true
        )
    ) {
        $lead->update([
            'status' => LeadStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }

    // Create call AFTER lead status update
    $call = $this->calls->createCall(
        $lead,
        $request->callAttributes(),
        $request->user(),
        $request->file('invoice_file')
    );

    return redirect()
        ->route('leads.show', $lead)
        ->with(
            'success',
            "Call logged as {$call->call_status->label()}."
        );
}

    public function show(Lead $lead, CallDetail $call): View
    {
        $this->authorize('view', $call);
        abort_unless($call->lead_id === $lead->id, 404);

        $call->load(['caller:id,name', 'lead:id,reference,name']);

        return view('call-details.show', [
            'pageTitle' => 'Call detail',
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => $lead->reference, 'route' => null],
                ['label' => 'Call'],
            ],
            'lead' => $lead,
            'call' => $call,
        ]);
    }

    public function edit(Lead $lead, CallDetail $call): View
    {
        $this->authorize('update', $call);
        abort_unless($call->lead_id === $lead->id, 404);

        return view('call-details.edit', [
            'pageTitle' => 'Edit call',
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => $lead->reference, 'route' => null],
                ['label' => 'Edit call'],
            ],
            'lead' => $lead,
            'call' => $call,
            ...$this->formOptions(request()),
        ]);
    }

    public function update(UpdateCallDetailRequest $request, Lead $lead, CallDetail $call): RedirectResponse
    {
        // The id is in the URL, so the parent must be verified — otherwise a
        // call could be edited through a lead it does not belong to.
        abort_unless($call->lead_id === $lead->id, 404);

        $this->calls->updateCall(
            $call,
            $request->callAttributes(),
            $request->file('invoice_file')
        );

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Call updated.');
    }

    public function destroy(Lead $lead, CallDetail $call): RedirectResponse
    {
        $this->authorize('delete', $call);
        abort_unless($call->lead_id === $lead->id, 404);

        $this->calls->deleteCall($call);

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', 'Call removed from the history.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $canAttribute = $request->user()->can('leads.manage');

        return [
            'statusOptions' => CallStatus::options(),
            'callerOptions' => $canAttribute ? StoreCallDetailRequest::callerOptions() : [],
            'canAttribute' => $canAttribute,
        ];
    }
}
