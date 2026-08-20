<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Support\QuotationReference;
use App\Models\CompanyProfile;
use App\Services\CompanyLogoService;
use App\Services\CallDetailService;
use App\Http\Requests\Api\StoreCallDetailRequest;
use App\Models\CallDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Lead\CloseLeadRequest;
use App\Enums\LeadStatus;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;

class LeadController extends Controller
{
    protected CompanyLogoService $logos;
    protected CallDetailService $calls;
   
       

   public function __construct(CompanyLogoService $logos,CallDetailService $calls,private readonly LeadService $service)
    {
    $this->logos = $logos;
    $this->calls = $calls;

    }
    // public function createLead(Request $request): JsonResponse
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'phone' => 'required|string|max:20',

    //         'source' => [
    //             'nullable',
    //             Rule::enum(LeadSource::class),
    //         ],

    //         'assigned_to' => 'nullable|exists:users,id',
    //         'description' => 'nullable|string',
    //     ]);

    //     $lastLead = Lead::latest('id')->first();

    //     $nextNumber = $lastLead
    //         ? ((int) str_replace('LEAD-', '', $lastLead->reference)) + 1
    //         : 1;

    //     $reference = 'LEAD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

    //     $lead = Lead::create([
    //         'reference' => $reference,
    //         'status' => 'new',
    //         'priority' => 'medium',
    //         'name' => $validated['name'],
    //         'phone' => $validated['phone'],
    //         'source' => $validated['source'] ?? null,
    //         'assigned_to' => $validated['assigned_to'] ?? null,
    //         'description' => $validated['description'] ?? null,
    //         'created_by' => auth()->id(),
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'status_code' => 201,
    //         'message' => 'Lead created successfully',
    //         'data' => $lead,
    //     ], 201);
    // }


    public function createLead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255',],
            'phone' => ['required','string','max:20',],
            'source' => ['nullable',Rule::enum(LeadSource::class),],
            'assigned_to' => ['nullable','exists:users,id',],
            'description' => ['nullable','string',],

            // QUOTATION - OPTIONAL
            'quotation' => ['nullable','array',],
            'quotation.customer_name' => ['required_with:quotation','string','max:255',],
            'quotation.customer_address' => ['nullable','string','max:1000',],
            'quotation.issue_date' => ['required_with:quotation','date',],
            'quotation.terms' => ['nullable','string',],

            // QUOTATION ITEMS - OPTIONAL
            'quotation.items' => ['required_with:quotation','array','min:1',],
            'quotation.items.*.description' => ['required','string','max:500',],
            'quotation.items.*.quantity' => ['required','numeric','min:1',],
            'quotation.items.*.rate' => ['required','numeric','min:0',],
            'quotation.items.*.tax_percent' => ['nullable','numeric','min:0','max:100',],
        ]);

        // CREATE LEAD
        $result = DB::transaction(function () use ($validated, $request) {
            // Generate Lead Reference
            $lastLead = Lead::latest('id')->first();
            $nextLeadNumber = $lastLead
                ? ((int) str_replace('LEAD-', '', $lastLead->reference)) + 1
                : 1;

            $leadReference = 'LEAD-' . str_pad(
                $nextLeadNumber,
                4,
                '0',
                STR_PAD_LEFT
            );

            // Create Lead
            $lead = Lead::create([
                'reference' => $leadReference,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'source' => $validated['source'] ?? null,
                'assigned_to' => $validated['assigned_to'] ?? null,
                'description' => $validated['description'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            // QUOTATION
            // Create only if quotation is provided

            $quotation = null;
            if (!empty($validated['quotation'])) {
                $quotationData = $validated['quotation'];
                // Generate Quotation Reference
                $quotation = QuotationReference::withNext(
                    function (string $quotationReference) use (
                        $lead,
                        $quotationData,
                        $request
                    ) {
                        // Calculate Subtotal
                        $quotationSubtotal = 0;
                        foreach ($quotationData['items'] as $item) {
                            $quantity = (float) $item['quantity'];
                            $rate = (float) $item['rate'];
                            $quotationSubtotal +=
                                $quantity * $rate;
                        }

                        $quotationSubtotal = round(
                            $quotationSubtotal,
                            2
                        );

                        // Create Quotation

                        $quotation = $lead->quotation()->create([

                            'reference' =>$quotationReference,
                            'customer_name' =>$quotationData['customer_name'],
                            'customer_address' =>$quotationData['customer_address']?? null,
                            'issue_date' =>$quotationData['issue_date'],
                            'terms' =>$quotationData['terms']?? null,
                            'subtotal' =>
                                number_format(
                                    $quotationSubtotal,
                                    2,
                                    '.',
                                    ''
                                ),

                            'discount_percent' => null,
                            'tax_percent' => null,
                            'total' => 0,
                            'created_by' =>$request->user()->id,
                        ]);


                        // Create Quotation Items

                        foreach (
                            $quotationData['items']
                            as $index => $item
                        ) {

                            $quantity =
                                (float) $item['quantity'];

                            $rate =
                                (float) $item['rate'];

                            $taxPercent =
                                isset($item['tax_percent'])
                                    ? (float) $item['tax_percent']
                                    : 18.0;


                            // Basic rate
                            $basicRate = $taxPercent > 0
                                ? round(
                                    $rate / (
                                        1 + ($taxPercent / 100)
                                    ),
                                    2
                                )
                                : $rate;


                            // Tax amount
                            $taxAmount = round(
                                ($rate - $basicRate) * $quantity,
                                2
                            );


                            // Amount
                            $amount = round(
                                $quantity * $rate,
                                2
                            );


                            // Create item
                            $quotation->items()->create([

                                'description' =>
                                    $item['description'],

                                'quantity' =>
                                    number_format(
                                        $quantity,
                                        2,
                                        '.',
                                        ''
                                    ),

                                'rate' =>
                                    number_format(
                                        $rate,
                                        2,
                                        '.',
                                        ''
                                    ),

                                'tax_percent' =>
                                    number_format(
                                        $taxPercent,
                                        2,
                                        '.',
                                        ''
                                    ),

                                'basic_rate' =>
                                    number_format(
                                        $basicRate,
                                        2,
                                        '.',
                                        ''
                                    ),

                                'tax_amount' =>
                                    number_format(
                                        $taxAmount,
                                        2,
                                        '.',
                                        ''
                                    ),

                                'amount' =>
                                    number_format(
                                        $amount,
                                        2,
                                        '.',
                                        ''
                                    ),

                                'sort_order' =>
                                    $index,
                            ]);
                        }

                        // Calculate Total
                        $quotationTotal =
                            $quotation
                                ->items()
                                ->sum('amount');

                        // Update Total
                        $quotation->update([

                            'total' =>
                                number_format(
                                    (float) $quotationTotal,
                                    2,
                                    '.',
                                    ''
                                ),
                        ]);


                        // Load items
                        $quotation->load('items');

                        return $quotation;
                    }
                );

                return $quotation;
            }

            // No quotation
            return [
                'lead' => $lead,
                'quotation' => null,
            ];
        });

        // API RESPONSE
        $quotation = $result['quotation'];
        return response()->json([

            'status' => true,
            'status_code' => 201,
            'message' => $quotation
                ? 'Lead and quotation created successfully'
                : 'Lead created successfully',
            'data' => [

                // LEAD
                'lead' => [
                    'id' =>$result['lead']->id,
                    'reference' =>$result['lead']->reference,
                    'name' =>$result['lead']->name,
                    'phone' =>$result['lead']->phone,
                    'source' =>$result['lead']->source?->value,
                    'assigned_to' =>$result['lead']->assigned_to,
                    'description' =>$result['lead']->description,
                    'created_by' =>$result['lead']->created_by,
                    'created_at' =>$result['lead']->created_at,
                ],

                // QUOTATION
                'quotation' => $quotation
                    ? [

                        'id' =>$quotation->id,
                        'reference' =>$quotation->reference,
                        'customer_name' =>$quotation->customer_name,
                        'customer_address' =>$quotation->customer_address,
                        'issue_date' =>$quotation->issue_date,
                        'terms' =>$quotation->terms,
                        'subtotal' =>$quotation->subtotal,
                        'total' =>$quotation->total,
                        'items' =>
                            $quotation->items
                                ->map(function ($item) {

                                    return [
                                        'id' =>$item->id,
                                        'description' =>$item->description,
                                        'quantity' =>$item->quantity,
                                        'rate' =>$item->rate,
                                        'basic_rate' =>$item->basic_rate,
                                        'tax_percent' =>$item->tax_percent,
                                        'tax_amount' =>$item->tax_amount,
                                        'amount' =>$item->amount,
                                        'sort_order' =>$item->sort_order,
                                    ];
                                })
                                ->values()
                                ->toArray(),

                    ]
                    : null,
            ],

        ], 201);
    }

    public function viewLead($id): JsonResponse
    {
        $lead = Lead::with([
            'owner:id,name',
            'creator:id,name',
            'quotation.items',
        ])->find($id);

        if (!$lead) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'Lead not found',
            ], 404);
        }

        $quotation = $lead->quotation;

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Lead details retrieved successfully',

            'data' => [
                'id' => $lead->id,
                'reference' => $lead->reference,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'source' => $lead->source?->value ?? $lead->source,
                'assigned_to' => $lead->owner?->name,
                'created_by' => $lead->creator?->name,
                'description' => $lead->description,

                'quotation' => $quotation ? [
                    'id' =>$quotation->id,
                    'reference' => $quotation->reference,
                    'customer_name' =>$quotation->customer_name,
                    'customer_address' =>$quotation->customer_address,
                    'issue_date' =>$quotation->issue_date,
                    'terms' =>$quotation->terms,
                    'subtotal' =>$quotation->subtotal,
                    'discount_percent' =>$quotation->discount_percent,
                    'tax_percent' =>$quotation->tax_percent,
                    'total' =>$quotation->total,

                    'items' => $quotation->items
                        ->map(function ($item) {
                            return [
                                'id' =>$item->id,
                                'description' =>$item->description,
                                'quantity' =>$item->quantity,
                                'rate' =>$item->rate,
                                'basic_rate' =>$item->basic_rate,
                                'tax_percent' =>$item->tax_percent,
                                'tax_amount' =>$item->tax_amount,
                                'amount' =>$item->amount,
                                'sort_order' =>$item->sort_order,
                            ];
                        })
                        ->values()
                        ->toArray(),

                ] : null,
            ],

        ], 200);
    }

    public function getLeadSources(): JsonResponse
    {
        $sources = collect(LeadSource::cases())->map(function ($source) {
            return [
                'value' => $source->value,
                'label' => str($source->value)->headline()->toString(),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Lead sources retrieved successfully',
            'data' => $sources,
        ], 200);
    }

    public function getLeadAssignees(): JsonResponse
    {
        $users = User::query()
            ->where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Lead assignees retrieved successfully',
            'data' => $users,
        ], 200);
    }

    public function updateLead(Request $request, $id): JsonResponse
    {
        // FIND LEAD
        $lead = Lead::with('quotation.items')->find($id);

        if (!$lead) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'Lead not found',
            ], 404);
        }


        // =====================================================
        // VALIDATION
        // =====================================================

        $validated = $request->validate([

            // =================================================
            // LEAD
            // =================================================

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
            ],

            'source' => [
                'nullable',
                Rule::enum(LeadSource::class),
            ],

            'assigned_to' => [
                'nullable',
                'exists:users,id',
            ],

            'description' => [
                'nullable',
                'string',
            ],


            // =================================================
            // QUOTATION - OPTIONAL
            // =================================================

            'quotation' => [
                'nullable',
                'array',
            ],

            'quotation.customer_name' => [
                'required_with:quotation',
                'string',
                'max:255',
            ],

            'quotation.customer_address' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'quotation.issue_date' => [
                'required_with:quotation',
                'date',
            ],

            'quotation.terms' => [
                'nullable',
                'string',
            ],


            // =================================================
            // QUOTATION ITEMS
            // =================================================

            'quotation.items' => [
                'required_with:quotation',
                'array',
                'min:1',
            ],

            'quotation.items.*.description' => [
                'required',
                'string',
                'max:500',
            ],

            'quotation.items.*.quantity' => [
                'required',
                'numeric',
                'min:1',
            ],

            'quotation.items.*.rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'quotation.items.*.tax_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
        ]);


        // =====================================================
        // UPDATE LEAD + QUOTATION
        // =====================================================

        $result = DB::transaction(function () use (
            $lead,
            $validated,
            $request
        ) {

            // =================================================
            // UPDATE LEAD
            // =================================================

            $lead->update([

                'name' =>
                    $validated['name'],

                'phone' =>
                    $validated['phone'],

                'source' =>
                    $validated['source'] ?? null,

                'assigned_to' =>
                    $validated['assigned_to'] ?? null,

                'description' =>
                    $validated['description'] ?? null,
            ]);


            // =================================================
            // QUOTATION
            // =================================================

            $quotation = $lead->quotation;


            // =================================================
            // IF QUOTATION IS PROVIDED
            // =================================================

            if (!empty($validated['quotation'])) {

                $quotationData =
                    $validated['quotation'];


                // =============================================
                // CREATE QUOTATION IF NOT EXISTS
                // =============================================

                if (!$quotation) {

                    $quotationReference =
                        QuotationReference::withNext(
                            function (
                                string $reference
                            ) use ($lead, $quotationData, $request) {

                                return $lead->quotation()->create([

                                    'reference' =>
                                        $reference,

                                    'customer_name' =>
                                        $quotationData['customer_name'],

                                    'customer_address' =>
                                        $quotationData['customer_address']
                                        ?? null,

                                    'issue_date' =>
                                        $quotationData['issue_date'],

                                    'terms' =>
                                        $quotationData['terms']
                                        ?? null,

                                    'subtotal' =>
                                        0,

                                    'discount_percent' =>
                                        null,

                                    'tax_percent' =>
                                        null,

                                    'total' =>
                                        0,

                                    'created_by' =>
                                        $request->user()->id,
                                ]);
                            }
                        );
                }


                // =============================================
                // UPDATE EXISTING QUOTATION
                // =============================================

                else {

                    $quotation->update([

                        'customer_name' =>
                            $quotationData['customer_name'],

                        'customer_address' =>
                            $quotationData['customer_address']
                            ?? null,

                        'issue_date' =>
                            $quotationData['issue_date'],

                        'terms' =>
                            $quotationData['terms']
                            ?? null,
                    ]);
                }


                // =============================================
                // CALCULATE SUBTOTAL
                // =============================================

                $quotationSubtotal = 0;

                foreach (
                    $quotationData['items']
                    as $item
                ) {

                    $quantity =
                        (float) $item['quantity'];

                    $rate =
                        (float) $item['rate'];

                    $quotationSubtotal +=
                        $quantity * $rate;
                }

                $quotationSubtotal =
                    round($quotationSubtotal, 2);


                // =============================================
                // DELETE OLD ITEMS
                // =============================================

                $quotation->items()->delete();


                // =============================================
                // CREATE NEW ITEMS
                // =============================================

                foreach (
                    $quotationData['items']
                    as $index => $item
                ) {

                    $quantity =
                        (float) $item['quantity'];

                    $rate =
                        (float) $item['rate'];

                    $taxPercent =
                        isset($item['tax_percent'])
                            ? (float) $item['tax_percent']
                            : 18.0;


                    // =========================================
                    // BASIC RATE
                    // =========================================

                    $basicRate = $taxPercent > 0
                        ? round(
                            $rate /
                            (1 + ($taxPercent / 100)),
                            2
                        )
                        : $rate;


                    // =========================================
                    // TAX AMOUNT
                    // =========================================

                    $taxAmount = round(
                        ($rate - $basicRate) * $quantity,
                        2
                    );


                    // =========================================
                    // AMOUNT
                    // =========================================

                    $amount = round(
                        $quantity * $rate,
                        2
                    );


                    // =========================================
                    // CREATE ITEM
                    // =========================================

                    $quotation->items()->create([

                        'description' =>
                            $item['description'],

                        'quantity' =>
                            number_format(
                                $quantity,
                                2,
                                '.',
                                ''
                            ),

                        'rate' =>
                            number_format(
                                $rate,
                                2,
                                '.',
                                ''
                            ),

                        'tax_percent' =>
                            number_format(
                                $taxPercent,
                                2,
                                '.',
                                ''
                            ),

                        'basic_rate' =>
                            number_format(
                                $basicRate,
                                2,
                                '.',
                                ''
                            ),

                        'tax_amount' =>
                            number_format(
                                $taxAmount,
                                2,
                                '.',
                                ''
                            ),

                        'amount' =>
                            number_format(
                                $amount,
                                2,
                                '.',
                                ''
                            ),

                        'sort_order' =>
                            $index,
                    ]);
                }


                // =============================================
                // CALCULATE TOTAL
                // =============================================

                $quotationTotal =
                    $quotation
                        ->items()
                        ->sum('amount');


                // =============================================
                // UPDATE QUOTATION TOTAL
                // =============================================

                $quotation->update([

                    'subtotal' =>
                        number_format(
                            $quotationSubtotal,
                            2,
                            '.',
                            ''
                        ),

                    'total' =>
                        number_format(
                            (float) $quotationTotal,
                            2,
                            '.',
                            ''
                        ),
                ]);


                // =============================================
                // LOAD ITEMS
                // =============================================

                $quotation->load('items');
            }


            // =================================================
            // RETURN
            // =================================================

            return [
                'lead' =>
                    $lead->fresh([
                        'owner:id,name',
                        'creator:id,name',
                        'quotation.items',
                    ]),

                'quotation' =>
                    $quotation?->fresh('items'),
            ];
        });


        // =====================================================
        // API RESPONSE
        // =====================================================

        return response()->json([

            'status' =>
                true,

            'status_code' =>
                200,

            'message' =>
                $result['quotation']
                    ? 'Lead and quotation updated successfully'
                    : 'Lead updated successfully',

            'data' => [

                // =================================================
                // LEAD
                // =================================================

                'lead' => [

                    'id' =>
                        $result['lead']->id,

                    'reference' =>
                        $result['lead']->reference,

                    'name' =>
                        $result['lead']->name,

                    'phone' =>
                        $result['lead']->phone,

                    'source' =>
                        $result['lead']->source?->value
                        ?? $result['lead']->source,

                    'assigned_to' =>
                        $result['lead']->owner?->name,

                    'created_by' =>
                        $result['lead']->creator?->name,

                    'description' =>
                        $result['lead']->description,
                ],


                // =================================================
                // QUOTATION
                // =================================================

                'quotation' =>
                    $result['quotation']
                        ? [

                            'id' =>
                                $result['quotation']->id,

                            'reference' =>
                                $result['quotation']->reference,

                            'customer_name' =>
                                $result['quotation']->customer_name,

                            'customer_address' =>
                                $result['quotation']->customer_address,

                            'issue_date' =>
                                $result['quotation']->issue_date,

                            'terms' =>
                                $result['quotation']->terms,

                            'subtotal' =>
                                $result['quotation']->subtotal,

                            'discount_percent' =>
                                $result['quotation']->discount_percent,

                            'tax_percent' =>
                                $result['quotation']->tax_percent,

                            'total' =>
                                $result['quotation']->total,


                            // =================================
                            // ITEMS
                            // =================================

                            'items' =>
                                $result['quotation']
                                    ->items
                                    ->map(function ($item) {

                                        return [

                                            'id' =>
                                                $item->id,

                                            'description' =>
                                                $item->description,

                                            'quantity' =>
                                                $item->quantity,

                                            'rate' =>
                                                $item->rate,

                                            'basic_rate' =>
                                                $item->basic_rate,

                                            'tax_percent' =>
                                                $item->tax_percent,

                                            'tax_amount' =>
                                                $item->tax_amount,

                                            'amount' =>
                                                $item->amount,

                                            'sort_order' =>
                                                $item->sort_order,
                                        ];
                                    })
                                    ->values()
                                    ->toArray(),

                        ]
                        : null,
            ],

        ], 200);
    }

    public function leadList(Request $request): JsonResponse
    {
        // PAGINATION
        $perPage = (int) $request->get('per_page', 10);
        $perPage = min(max($perPage, 1), 100);

        // SEARCH
        $search = trim($request->get('search', ''));
        $query = Lead::with([
            'owner:id,name',
            'creator:id,name',
        ]);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($ownerQuery) use ($search) {
                        $ownerQuery->where('name', 'like', "%{$search}%");
                    });

            });
        }
        $leads = $query->latest('id')->paginate($perPage);
        $data = $leads->getCollection()->map(function ($lead) {
            return [
                'id' =>$lead->id,
                'reference' =>$lead->reference,
                'name' =>$lead->name,
                'phone' =>$lead->phone,
                'source' =>$lead->source?->value ?? $lead->source,
                'assigned_to' =>$lead->owner?->name,
                'created_by' =>$lead->creator?->name,
                'description' =>$lead->description,
                 // Quotation exists or not
                'has_quotation' => (bool) $lead->quotation_exists,
            ];
            })->values();
        return response()->json([

            'status' =>true,
            'status_code' =>200,
            'message' =>'Leads retrieved successfully',
            'data' =>$data,

            'pagination' => [
                'current_page' =>$leads->currentPage(),
                'per_page' =>$leads->perPage(),
                'total' =>$leads->total(),
                'last_page' =>$leads->lastPage(),
                'from' =>$leads->firstItem(),
                'to' =>$leads->lastItem(),
            ],

        ], 200);
    }

    // public function quotationPdfDetails(
    //         Request $request,
    //         Lead $lead
    //     ): JsonResponse {

    //     // FIND QUOTATION

    //     $quotation = $lead->quotation;

    //     if (!$quotation) {
    //         return response()->json([
    //             'status' => false,
    //             'status_code' => 404,
    //             'message' => 'Quotation not found for this lead',
    //         ], 404);
    //     }

    //     // LOAD ITEMS
    //     $quotation->load('items');

    //     // =========================================
    //     // COMPANY
    //     // =========================================

    //     $company = CompanyProfile::current();

    //     // =========================================
    //     // RESPONSE
    //     // =========================================

    //     return response()->json([

    //         'status' => true,

    //         'status_code' => 200,

    //         'message' => 'Quotation PDF details retrieved successfully',

    //         'data' => [

    //             // =====================================
    //             // LEAD DETAILS
    //             // =====================================

    //             'lead' => [

    //                 'id' =>
    //                     $lead->id,

    //                 'reference' =>
    //                     $lead->reference,

    //                 'name' =>
    //                     $lead->name,

    //                 'phone' =>
    //                     $lead->phone,

    //                 'source' =>
    //                     $lead->source?->value ?? $lead->source,

    //                 'assigned_to' =>
    //                     $lead->owner?->name,

    //                 'description' =>
    //                     $lead->description,
    //             ],

    //             // =====================================
    //             // QUOTATION DETAILS
    //             // =====================================

    //             'quotation' => [

    //                 'id' =>
    //                     $quotation->id,

    //                 'reference' =>
    //                     $quotation->reference,

    //                 'customer_name' =>
    //                     $quotation->customer_name,

    //                 'customer_address' =>
    //                     $quotation->customer_address,

    //                 'issue_date' =>
    //                     $quotation->issue_date,

    //                 'terms' =>
    //                     $quotation->terms,

    //                 'subtotal' =>
    //                     $quotation->subtotal,

    //                 'discount_percent' =>
    //                     $quotation->discount_percent,

    //                 'tax_percent' =>
    //                     $quotation->tax_percent,

    //                 'total' =>
    //                     $quotation->total,

    //                 // =================================
    //                 // ITEMS
    //                 // =================================

    //                 'items' =>
    //                     $quotation->items
    //                         ->map(function ($item) {

    //                             return [

    //                                 'id' =>
    //                                     $item->id,

    //                                 'description' =>
    //                                     $item->description,

    //                                 'quantity' =>
    //                                     $item->quantity,

    //                                 'rate' =>
    //                                     $item->rate,

    //                                 'basic_rate' =>
    //                                     $item->basic_rate,

    //                                 'tax_percent' =>
    //                                     $item->tax_percent,

    //                                 'tax_amount' =>
    //                                     $item->tax_amount,

    //                                 'amount' =>
    //                                     $item->amount,

    //                                 'sort_order' =>
    //                                     $item->sort_order,
    //                             ];
    //                         })
    //                         ->values()
    //                         ->toArray(),
    //             ],

    //             // =====================================
    //             // COMPANY DETAILS
    //             // =====================================

    //             'company' => [

    //                 'id' =>
    //                     $company->id,

    //                 'name' =>
    //                     $company->name,

    //                 'address_line' =>
    //                     $company->address_line,

    //                 'city' =>
    //                     $company->city,

    //                 'state' =>
    //                     $company->state,

    //                 'postal_code' =>
    //                     $company->postal_code,

    //                 'country' =>
    //                     $company->country,

    //                 'phone' =>
    //                     $company->phone,

    //                 'email' =>
    //                     $company->email,

    //                 'logo' => $company->logo_path
    //                 ? url('storage/' . $company->logo_path)
    //                 : null,
    //             ],
    //         ],

    //     ], 200);
    // }
public function quotationPdfDetails(
    Request $request,
    Lead $lead
): JsonResponse {

    // =========================================
    // FIND QUOTATION
    // =========================================

    $quotation = $lead->quotation;

    if (!$quotation) {
        return response()->json([
            'status' => false,
            'status_code' => 404,
            'message' => 'Quotation not found for this lead',
        ], 404);
    }


    // =========================================
    // LOAD ITEMS
    // =========================================

    $quotation->load('items');


    // =========================================
    // COMPANY
    // =========================================

    $company = CompanyProfile::current();


    // =========================================
    // LOGO DATA URI
    // =========================================

    $logoDataUri = null;

    if ($company?->logo_path) {

        $logoPath = storage_path(
            'app/public/' . $company->logo_path
        );

        if (file_exists($logoPath)) {

            $mimeType = mime_content_type($logoPath);

            $logoDataUri = 'data:' . $mimeType . ';base64,' .
                base64_encode(
                    file_get_contents($logoPath)
                );
        }
    }


    // =========================================
    // GENERATE PDF
    // =========================================

    $pdf = Pdf::loadView('quotations.pdf', [
        'lead' => $lead,
        'quotation' => $quotation,
        'company' => $company,
        'logoDataUri' => $logoDataUri,
    ]);


    // =========================================
    // FILE NAME
    // =========================================


    $fileName = $quotation->reference . '.pdf';

    $path = 'quotations/' . $fileName;

    Storage::disk('public')->put(
        $path,
        $pdf->output()
    );

    $pdfUrl = url('storage/' . $path);


    // =========================================
    // RESPONSE
    // =========================================

    return response()->json([

        'status' => true,

        'status_code' => 200,

        'message' => 'Quotation PDF generated successfully',

        'data' => [

            // =====================================
            // LEAD DETAILS
            // =====================================

            'lead' => [

                'id' =>
                    $lead->id,

                'reference' =>
                    $lead->reference,

                'name' =>
                    $lead->name,

                'phone' =>
                    $lead->phone,

                'source' =>
                    $lead->source?->value ?? $lead->source,

                'assigned_to' =>
                    $lead->owner?->name,

                'description' =>
                    $lead->description,
            ],


            // =====================================
            // QUOTATION DETAILS
            // =====================================

            'quotation' => [

                'id' =>
                    $quotation->id,

                'reference' =>
                    $quotation->reference,

                'customer_name' =>
                    $quotation->customer_name,

                'customer_address' =>
                    $quotation->customer_address,

                'issue_date' =>
                    $quotation->issue_date,

                'terms' =>
                    $quotation->terms,

                'subtotal' =>
                    $quotation->subtotal,

                'discount_percent' =>
                    $quotation->discount_percent,

                'tax_percent' =>
                    $quotation->tax_percent,

                'total' =>
                    $quotation->total,


                // =================================
                // ITEMS
                // =================================

                'items' => $quotation->items
                    ->map(function ($item) {

                        return [

                            'id' =>
                                $item->id,

                            'description' =>
                                $item->description,

                            'quantity' =>
                                $item->quantity,

                            'rate' =>
                                $item->rate,

                            'basic_rate' =>
                                $item->basic_rate,

                            'tax_percent' =>
                                $item->tax_percent,

                            'tax_amount' =>
                                $item->tax_amount,

                            'amount' =>
                                $item->amount,

                            'sort_order' =>
                                $item->sort_order,
                        ];
                    })
                    ->values()
                    ->toArray(),
            ],


            // =====================================
            // COMPANY DETAILS
            // =====================================

            'company' => [

                'id' =>
                    $company?->id,

                'name' =>
                    $company?->name,

                'address_line' =>
                    $company?->address_line,

                'city' =>
                    $company?->city,

                'state' =>
                    $company?->state,

                'postal_code' =>
                    $company?->postal_code,

                'country' =>
                    $company?->country,

                'phone' =>
                    $company?->phone,

                'email' =>
                    $company?->email,

                'logo' => $company?->logo_path
                    ? url('storage/' . $company->logo_path)
                    : null,
            ],


            // =====================================
            // PDF URL
            // =====================================

            'pdf_url' => $pdfUrl,
        ],

    ], 200);
}

    public function addCall(StoreCallDetailRequest $request, Lead $lead): JsonResponse
    {
        try {

            $call = $this->calls->createCall(
                $lead,
                $request->callAttributes(),
                $request->user(),
                $request->file('invoice_file')
            );

            $call->load([
                'caller:id,name',
                'lead:id,reference,name',
            ]);

            return response()->json([
                'status' => true,
                'status_code' => 201,
                'message' => "Call logged as {$call->call_status->label()}.",
                'data' => [
                    'id' => $call->id,
                    'lead_id' => $call->lead_id,
                    'lead_reference' => $call->lead?->reference,
                    'lead_name' => $call->lead?->name,
                    'called_by' => $call->caller?->name,
                    'called_date' => $call->called_date,
                    'called_time' => $call->called_time,
                    'duration' => $call->duration,
                    'call_status' => $call->call_status?->value,
                    'call_status_label' => $call->call_status?->label(),
                    'interest' => $call->interest,
                    'reason' => $call->reason,
                    'is_item_sold' => $call->is_item_sold,
                    'invoice_number' => $call->invoice_number,
                    'next_followup_date' => $call->next_followup_date,
                    'remarks' => $call->remarks,
                    'invoice_file' => $call->invoiceUrl(),
                    'created_at' => $call->created_at,
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Failed to log call.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCallDetails(Lead $lead, CallDetail $call): JsonResponse
    {
        try {

            if ($call->lead_id !== $lead->id) {
                return response()->json([
                    'status' => false,
                    'status_code' => 404,
                    'message' => 'Call does not belong to this lead.',
                    'data' => null,
                ], 404);
            }

            $call->load([
                'caller:id,name',
                'lead:id,reference,name',
            ]);

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => 'Call detail retrieved successfully.',
                'data' => [
                    'id' => $call->id,
                    'lead_id' => $call->lead_id,
                    'lead_reference' => $call->lead?->reference,
                    'lead_name' => $call->lead?->name,
                    'called_by' => $call->caller?->name,
                    'called_date' => $call->called_date,
                    'called_time' => $call->called_time,
                    'duration' => $call->duration,
                    'call_status' => $call->call_status?->value,
                    'call_status_label' => $call->call_status?->label(),
                    'interest' => $call->interest,
                    'reason' => $call->reason,
                    'is_item_sold' => $call->is_item_sold,
                    'invoice_number' => $call->invoice_number,
                    'next_followup_date' => $call->next_followup_date,
                    'remarks' => $call->remarks,
                    'invoice_file' => $call->invoiceUrl(),
                    'created_at' => $call->created_at,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => 'Failed to retrieve call details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function closeLead(CloseLeadRequest $request,Lead $lead): JsonResponse 
    {
        try {

            $status = LeadStatus::from(
                $request->validated('status')
            );

            $this->service->close(
                $lead,
                $status,
                $request->validated('lost_reason')
            );

            $lead->refresh();

            return response()->json([
                'status' => true,
                'status_code' => 200,
                'message' => "Lead {$lead->reference} was marked {$status->label()}.",
                'data' => [
                    'id' => $lead->id,
                    'reference' => $lead->reference,
                    'status' => $lead->status,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

   
    public function callHistory(Request $request, Lead $lead): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $calls = $lead->callDetails()
            ->with([
                'caller:id,name',
            ])
            ->latest('called_date')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Call history fetched successfully.',
            'data' => $calls->map(function ($call) {
                return [
                    'id' => $call->id,
                    'called_date' => $call->called_date?->format('d-M-Y'),
                    'called_time' => $call->calledAt()?->format('g:i A'),

                    'call_status' => $call->call_status,

                    'interest' => $call->interest,

                    'reason' => $call->reason,

                    'is_item_sold' => $call->is_item_sold,

                    'invoice_number' => $call->invoice_number,

                    'invoice_file_path' => $call->invoice_file_path,

                    'invoice_url' => $call->invoice_file_path
                        ? asset('storage/' . $call->invoice_file_path)
                        : null,

                    'next_followup_date' => $call->next_followup_date,

                    'called_by' => $call->caller
                        ? [
                            'id' => $call->caller->id,
                            'name' => $call->caller->name,
                        ]
                        : null,

                    'remarks' => $call->remarks,

                    'created_at' => $call->created_at,
                ];
            }),

            'pagination' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
                'from' => $calls->firstItem(),
                'to' => $calls->lastItem(),
            ],
        ]);
    }


}