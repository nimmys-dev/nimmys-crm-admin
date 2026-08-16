<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    public function createLead(Request $request): JsonResponse
    {
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

        $lastLead = Lead::latest('id')->first();

        $nextNumber = $lastLead
            ? ((int) str_replace('LEAD-', '', $lastLead->reference)) + 1
            : 1;

        $reference = 'LEAD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $lead = Lead::create([
            'reference' => $reference,
            'status' => 'new',
            'priority' => 'medium',
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'source' => $validated['source'] ?? null,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'status_code' => 201,
            'message' => 'Lead created successfully',
            'data' => $lead,
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