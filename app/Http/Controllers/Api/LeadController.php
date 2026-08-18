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

class LeadController extends Controller
{
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

            // =====================================================
            // LEAD
            // =====================================================

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

            // =====================================================
            // QUOTATION - OPTIONAL
            // =====================================================

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

            // =====================================================
            // QUOTATION ITEMS - OPTIONAL
            // =====================================================

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
        // CREATE LEAD
        // =====================================================

        $result = DB::transaction(function () use ($validated, $request) {

            // -----------------------------------------------------
            // Generate Lead Reference
            // -----------------------------------------------------

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


            // -----------------------------------------------------
            // Create Lead
            // -----------------------------------------------------

            $lead = Lead::create([

                'reference' => $leadReference,

                'name' => $validated['name'],

                'phone' => $validated['phone'],

                'source' => $validated['source'] ?? null,

                'assigned_to' => $validated['assigned_to'] ?? null,

                'description' => $validated['description'] ?? null,

                'created_by' => $request->user()->id,
            ]);


            // =====================================================
            // QUOTATION
            // Create only if quotation is provided
            // =====================================================

            $quotation = null;

            if (!empty($validated['quotation'])) {

                $quotationData = $validated['quotation'];


                // -------------------------------------------------
                // Generate Quotation Reference
                // -------------------------------------------------

                $quotation = QuotationReference::withNext(
                    function (string $quotationReference) use (
                        $lead,
                        $quotationData,
                        $request
                    ) {

                        // -----------------------------------------
                        // Calculate Subtotal
                        // -----------------------------------------

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


                        // -----------------------------------------
                        // Create Quotation
                        // -----------------------------------------

                        $quotation = $lead->quotation()->create([

                            'reference' =>
                                $quotationReference,

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
                                number_format(
                                    $quotationSubtotal,
                                    2,
                                    '.',
                                    ''
                                ),

                            'discount_percent' => null,

                            'tax_percent' => null,

                            'total' => 0,

                            'created_by' =>
                                $request->user()->id,
                        ]);


                        // -----------------------------------------
                        // Create Quotation Items
                        // -----------------------------------------

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


                        // -----------------------------------------
                        // Calculate Total
                        // -----------------------------------------

                        $quotationTotal =
                            $quotation
                                ->items()
                                ->sum('amount');


                        // -----------------------------------------
                        // Update Total
                        // -----------------------------------------

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


        // =====================================================
        // API RESPONSE
        // =====================================================

        $quotation = $result['quotation'];


        return response()->json([

            'status' => true,

            'status_code' => 201,

            'message' => $quotation
                ? 'Lead and quotation created successfully'
                : 'Lead created successfully',

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
                        $result['lead']->source?->value,

                    'assigned_to' =>
                        $result['lead']->assigned_to,

                    'description' =>
                        $result['lead']->description,

                    'created_by' =>
                        $result['lead']->created_by,

                    'created_at' =>
                        $result['lead']->created_at,
                ],

                // =================================================
                // QUOTATION
                // =================================================

                'quotation' => $quotation
                    ? [

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

                        'total' =>
                            $quotation->total,

                        'items' =>
                            $quotation->items
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

        ], 201);
    }




    public function viewLead($id): JsonResponse
    {
        $lead = Lead::with([
            'owner:id,name',
            'creator:id,name',
        ])->find($id);

        if (!$lead) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'Lead not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Lead details retrieved successfully',
            'data' => [
                'id' => $lead->id,
                'reference' => $lead->reference,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'source' => $lead->source,
                'assigned_to' => $lead->owner?->name,
                'created_by' => $lead->creator?->name,
                'description' => $lead->description,
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
        $lead = Lead::find($id);

        if (!$lead) {
            return response()->json([
                'status' => false,
                'status_code' => 404,
                'message' => 'Lead not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',

            'source' => [
                'nullable',
                Rule::enum(LeadSource::class),
            ],

            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $lead->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'source' => $validated['source'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Lead updated successfully',
            'data' => $lead->fresh(),
        ], 200);
    }

    public function leadList(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);

        $leads = Lead::with([
            'owner:id,name',
            'creator:id,name',
        ])
            ->latest('id')
            ->paginate($perPage);

        $data = $leads->getCollection()->map(function ($lead) {
            return [
                'id' => $lead->id,
                'reference' => $lead->reference,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'source' => $lead->source,
                'assigned_to' => $lead->owner?->name,
                'created_by' => $lead->creator?->name,
                'description' => $lead->description,
            ];
        });

        return response()->json([
            'status' => true,
            'status_code' => 200,
            'message' => 'Leads retrieved successfully',
            'data' => $data,
            'pagination' => [
                'current_page' => $leads->currentPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
                'last_page' => $leads->lastPage(),
                'from' => $leads->firstItem(),
                'to' => $leads->lastItem(),
            ],
        ], 200);
    }
}