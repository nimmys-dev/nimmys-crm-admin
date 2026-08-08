<?php

namespace App\Http\Controllers;

use App\Contracts\LeadRepository;
use App\Enums\FollowUpType;
use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Requests\Lead\AssignLeadRequest;
use App\Http\Requests\Lead\LeadIndexRequest;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\Shop;
use App\Services\LeadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lead Management.
 *
 * Reads go through LeadRepository, which narrows every query to what the
 * viewer may see. Writes go through LeadService, which owns the business
 * rules. This controller only wires request to response.
 *
 * Route access is gated by the `leads` middleware (module access) and each
 * action by LeadPolicy (record-level ownership).
 */
class LeadController extends Controller
{
    /*
     * Authorised per action rather than with authorizeResource(): Laravel 11
     * controllers no longer have a middleware() method, which that helper
     * relies on. index, store and update are covered by their form requests;
     * the rest call the policy directly.
     */
    public function __construct(
        private readonly LeadRepository $leads,
        private readonly LeadService $service,
    ) {}

    public function index(LeadIndexRequest $request): View
    {
        return view('leads.index', [
            'pageTitle' => 'Lead Management',
            'breadcrumbs' => [['label' => 'Leads']],
            'leads' => $this->leads->paginate($request->user(), $request->filters()),
            'filters' => $request->filters(),
            'hasActiveFilters' => $request->hasActiveFilters(),
            'statistics' => $this->leads->statistics($request->user()),
            ...$this->formOptions($request),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Lead::class);

        return view('leads.create', [
            'pageTitle' => 'Add Lead',
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => 'Add'],
            ],
            'lead' => new Lead,
            ...$this->formOptions($request),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = $this->service->create($request->leadAttributes(), $request->user());

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', "Lead {$lead->reference} was created.");
    }

    public function show(Request $request, Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load([
            'owner:id,name,email',
            'shop:id,name',
            'creator:id,name',
            'followUps.user:id,name',
        ]);

        return view('leads.show', [
            'pageTitle' => $lead->name,
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => $lead->reference],
            ],
            'lead' => $lead,
            'followUpTypes' => FollowUpType::options(),
            'assignableUsers' => $request->user()->can('leads.assign')
                ? AssignLeadRequest::assignableOptions()
                : [],
        ]);
    }

    public function edit(Request $request, Lead $lead): View
    {
        $this->authorize('update', $lead);

        return view('leads.edit', [
            'pageTitle' => "Edit {$lead->reference}",
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => $lead->reference, 'route' => null],
                ['label' => 'Edit'],
            ],
            'lead' => $lead,
            ...$this->formOptions($request),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $this->service->update($lead, $request->leadAttributes(), $request->user());

        return redirect()
            ->route('leads.show', $lead)
            ->with('success', "Lead {$lead->reference} was updated.");
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $reference = $lead->reference;

        $this->service->delete($lead);

        return redirect()
            ->route('leads.index')
            ->with('success', "Lead {$reference} was deleted.");
    }

    /**
     * Shared option lists for the create and edit forms.
     *
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $canAssign = $request->user()->can('leads.assign');

        return [
            'statusOptions' => LeadStatus::options(),
            'sourceOptions' => LeadSource::options(),
            'priorityOptions' => LeadPriority::options(),
            'shopOptions' => Shop::query()->orderBy('name')->pluck('name', 'id')->all(),

            // Empty for users who may not assign, so the select never offers
            // a choice the request would strip.
            'assignableUsers' => $canAssign ? AssignLeadRequest::assignableOptions() : [],
            'canAssign' => $canAssign,
        ];
    }
}
