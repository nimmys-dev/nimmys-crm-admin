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
use App\Models\User;

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

    // public function index(LeadIndexRequest $request): View
    // {
    //     return view('leads.index', [
    //         'pageTitle' => 'Lead Management',
    //         'breadcrumbs' => [['label' => 'Leads']],
    //         'leads' => $this->leads->paginate($request->user(), $request->filters()),
    //         'filters' => $request->filters(),
    //         'hasActiveFilters' => $request->hasActiveFilters(),
    //         'statistics' => $this->leads->statistics($request->user()),
    //         ...$this->formOptions($request),
    //     ]);
    // }

    // public function index(LeadIndexRequest $request): View
    // {
    //     $user = $request->user();
    //     $filters = $request->filters();
    //     $dashboardFilter = $request->query('filter');
    //     $query = Lead::query()->with(['owner','latestCall',]);

    //     /* Dashboard Filter */

    //     switch ($dashboardFilter) {

    //         case 'unattended':

    //             $query->whereNull('assigned_to');

    //             if (!$user->isAdmin()) {
    //                 $query->where('assigned_to', $user->id);
    //             }

    //             break;

    //         case 'today_followup':

    //             if (!$user->isAdmin()) {
    //                 $query->where('assigned_to', $user->id);
    //             }

    //             $query->whereHas('latestCall', function ($q) {
    //                 $q->whereDate('next_followup_date', today());
    //             });

    //             break;

    //         case 'overdue_followup':

    //             if (!$user->isAdmin()) {
    //                 $query->where('assigned_to', $user->id);
    //             }

    //             $query->whereHas('latestCall', function ($q) {
    //                 $q->whereDate('next_followup_date', '<', today());
    //             });

    //             break;

    //         case 'upcoming_followup':

    //             if (!$user->isAdmin()) {
    //                 $query->where('assigned_to', $user->id);
    //             }

    //             $query->whereHas('latestCall', function ($q) {
    //                 $q->whereDate('next_followup_date', '>', today());
    //             });

    //             break;

    //         case 'my_leads':
    //         case 'your_leads':

    //             $query->where('assigned_to', $user->id);

    //             break;
    //     }

    //     /* Search */

    //     if ($request->filled('q')) {
    //         $search = $request->q;
    //         $query->where(function ($q) use ($search) {
    //             $q->where('name', 'like', "%{$search}%")
    //                 ->orWhere('company', 'like', "%{$search}%")
    //                 ->orWhere('email', 'like', "%{$search}%")
    //                 ->orWhere('phone', 'like', "%{$search}%")
    //                 ->orWhere('reference', 'like', "%{$search}%");

    //         });
    //     }

    //     /* Status */
    //     if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     /*Priority*/
    //     if ($request->filled('priority')) {
    //         $query->where('priority', $request->priority);
    //     }

    //     /*Source*/
    //     if ($request->filled('source')) {
    //         $query->where('source', $request->source);
    //     }

    //     /*Assigned User*/
    //     if ($request->filled('assigned_to')) {
    //         $query->where('assigned_to', $request->assigned_to);
    //     }

    //     /*Shop*/
    //     if ($request->filled('shop_id')) {
    //         $query->where('shop_id', $request->shop_id);
    //     }

    //     /*Due Filter*/
    //     if ($request->input('due') === 'overdue') {
    //         $query->whereHas('latestCall', function ($q) {
    //             $q->whereDate('next_followup_date', '<', today());
    //         });
    //     }

    //     /*Sorting*/
    //     $sort = $request->input('sort', 'created_at');
    //     $direction = $request->input('direction', 'desc');
    //     $query->orderBy($sort, $direction);

    //     /*Pagination*/
    //     $leads = $query
    //         ->paginate($request->input('per_page', 15))
    //         ->withQueryString();

    //     return view('leads.index', [
    //         'pageTitle' => 'Lead Management',
    //         'breadcrumbs' => [
    //             ['label' => 'Leads']
    //         ],

    //         'filters' => $filters,
    //         'leads' => $leads,
    //         'hasActiveFilters' => $request->hasActiveFilters(),
    //         'statistics' => $this->leads->statistics(
    //             $request->user()
    //         ),
    //         ...$this->formOptions($request),
    //     ]);
    // }
public function getDashboardLeadStatistics(User $user): array
{
    // Role check controller-ൽ ഉള്ളതുപോലെ തന്നെ uniform ആക്കുക
    $isAdmin = (isset($user->role->value) && $user->role->value === 'admin') || $user->isAdmin();

    $query = Lead::query();

    if (!$isAdmin) {
        $query->where('assigned_to', $user->id);
    }

    return [
        'unattended' => (clone $query)
            ->whereDoesntHave('callDetails')
            ->count(),

        // callDetails ന് പകരം latestCall ഉപയോഗിക്കുക
        'today_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', today());
            })
            ->count(),

        'overdue_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '<', today());
            })
            ->count(),

        'upcoming_followup' => (clone $query)
            ->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '>', today());
            })
            ->count(),

        'your_leads' => Lead::query()
            ->where('assigned_to', $user->id)
            ->where('status', '!=', 'closed')
            ->count(),

        'total_leads' => Lead::query() ->where('status', '!=', 'closed') ->count(),
    ];
}
public function index(LeadIndexRequest $request): View
{
    $user = $request->user();
    $filters = $request->filters();
    $dashboardFilter = $request->query('filter');

    $isAdmin = (isset($user->role->value) && $user->role->value === 'admin') || $user->isAdmin();

    $query = Lead::query()->with([
        'owner',
        'latestCall',
    ]);

    /* Dashboard Filter Logic */
    switch ($dashboardFilter) {

        case 'unattended':
            $query->whereDoesntHave('callDetails');
            if (!$isAdmin) {
                $query->where('assigned_to', $user->id);
            }
            break;

        case 'today_followup':
            if (!$isAdmin) {
                $query->where('assigned_to', $user->id);
            }
            $query->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', today());
            });
            break;

        case 'overdue_followup':
            if (!$isAdmin) {
                $query->where('assigned_to', $user->id);
            }
            $query->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '<', today());
            });
            break;

        case 'upcoming_followup':
            if (!$isAdmin) {
                $query->where('assigned_to', $user->id);
            }
            $query->whereHas('latestCall', function ($q) {
                $q->whereNotNull('next_followup_date')
                  ->whereDate('next_followup_date', '>', today());
            });
            break;

        case 'my_leads':
        case 'your_leads':
            $query->where('assigned_to', $user->id)->where('status', '!=', 'closed');
            break;

        case 'total_leads':
                $query->where('status', '!=', 'closed');
            break;
            
        default:
            // Sidebar-ലെ 'Lead Management' ക്ലിക്ക് ചെയ്ത് വരുമ്പോൾ 
            // Normal Employee ആണെങ്കിൽ സ്വന്തം Leads മാത്രം കാണിക്കും
            if (!$isAdmin) {
                $query->where('assigned_to', $user->id);
            }
            break;
    }
$filter = $request->query('filter');

if ($filter) {
    $query->where('status', '!=', 'closed') ->where('status', '!=', 'lost');
}

switch ($filter) {

    case 'unattended':
        $query->whereDoesntHave('callDetails');
        break;

    case 'today_followup':
        $query->whereHas('latestCall', function ($q) {
            $q->whereNotNull('next_followup_date')
              ->whereDate('next_followup_date', today());
        });
        break;

    case 'overdue_followup':
        $query->whereHas('latestCall', function ($q) {
            $q->whereNotNull('next_followup_date')
              ->whereDate('next_followup_date', '<', today());
        });
        break;

    case 'upcoming_followup':
        $query->whereHas('latestCall', function ($q) {
            $q->whereNotNull('next_followup_date')
              ->whereDate('next_followup_date', '>', today());
        });
        break;

    case 'total_leads':
        // All open leads
        break;

    default:
        if (!$isAdmin) {
            $query->where('assigned_to', $user->id);
        }
        break;
}
    /* Standard Search & Filters */
    if ($request->filled('q')) {
        $search = $request->q;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%");
        });
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('priority')) {
        $query->where('priority', $request->priority);
    }
    if ($request->filled('source')) {
        $query->where('source', $request->source);
    }
    if ($request->filled('assigned_to')) {
        $query->where('assigned_to', $request->assigned_to);
    }
    if ($request->filled('shop_id')) {
        $query->where('shop_id', $request->shop_id);
    }

    $sort = $request->input('sort', 'created_at');
    $direction = $request->input('direction', 'desc');

    $leads = $query->orderBy($sort, $direction)
        ->paginate($request->input('per_page', 15))
        ->withQueryString();

    return view('leads.index', [
        'pageTitle' => 'Lead Management',
        'breadcrumbs' => [['label' => 'Leads']],
        'filters' => $filters,
        'leads' => $leads,
        'hasActiveFilters' => $request->hasActiveFilters(),
        'statistics' => $this->getDashboardLeadStatistics($user),
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
            // Newest call only, for the "Latest status" detail field.
            'latestCall',
            'quotation',
        ]);

        return view('leads.show', [
            'pageTitle' => $lead->name,
            'breadcrumbs' => [
                ['label' => 'Leads', 'route' => 'leads.index'],
                ['label' => $lead->reference],
            ],
            'lead' => $lead,
            'latestCall' => $lead->latestCall,
            'closeOptions' => collect(LeadStatus::closed())
                ->mapWithKeys(fn (LeadStatus $status) => [$status->value => $status->label()])
                ->all(),
            'followUpTypes' => FollowUpType::options(),
            // 'assignableUsers' => $request->user()->can('leads.assign')
            //     ? AssignLeadRequest::assignableOptions()
            //     : [],
            'assignableUsers' => AssignLeadRequest::assignableOptions(),
            'activities' => app(\App\Services\LeadActivityService::class)->getActivitiesForLead($lead),
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
