<?php

namespace App\Http\Controllers;

use App\Http\Requests\Lead\AssignLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\FirebaseNotificationService;

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

    // public function update(AssignLeadRequest $request, Lead $lead): RedirectResponse
    // {
    //     $userId = $request->validated('assigned_to');

    //     $this->service->assign($lead, $userId ? (int) $userId : null);

    //     $owner = $userId ? User::find($userId)?->name : null;

    //     return back()->with(
    //         'success',
    //         $owner
    //             ? "Lead {$lead->reference} was assigned to {$owner}."
    //             : "Lead {$lead->reference} is now unassigned."
    //     );
    // }

//     public function update(Request $request, Lead $lead): RedirectResponse
// {
//     $request->validate([
//         'assigned_to' => [
//             'nullable',
//             'exists:users,id',
//         ],
//     ]);

//     $userId = $request->input('assigned_to');

//     $this->service->assign(
//         $lead,
//         $userId ? (int) $userId : null
//     );

//     $owner = $userId
//         ? User::find($userId)?->name
//         : null;

//     return redirect()
//     ->route('dashboard')
//     ->with(
//         'success',
//         $owner
//             ? "Lead {$lead->reference} was assigned to {$owner}."
//             : "Lead {$lead->reference} is now unassigned."
//     );
// }

public function update(Request $request, Lead $lead): RedirectResponse
{
    $request->validate([
        'assigned_to' => [
            'nullable',
            'exists:users,id',
        ],
    ]);

    $userId = $request->input('assigned_to');

    // Assign lead
    $this->service->assign(
        $lead,
        $userId ? (int) $userId : null
    );

    // Get assigned user
    $ownerUser = $userId
        ? User::find($userId)
        : null;

    // Owner name for success message
    $owner = $ownerUser?->name;

    /*
    |--------------------------------------------------------------------------
    | Send FCM notification
    |--------------------------------------------------------------------------
    */

    if ($ownerUser && !empty($ownerUser->fcm_token)) {
        try {

            $firebaseService = app(FirebaseNotificationService::class);

            $firebaseService->sendToUser(
                $ownerUser,
                'Lead Assigned',
                'A lead has been assigned to you: ' . $lead->reference,
                [
                    'type'      => 'lead_assigned',
                    'lead_id'   => (string) $lead->id,
                    'reference' => (string) $lead->reference,
                ]
            );

            \Log::info('Lead assignment FCM notification sent', [
                'lead_id'     => $lead->id,
                'assigned_to' => $ownerUser->id,
            ]);

        } catch (\Throwable $e) {

            \Log::error('Lead assignment FCM notification failed', [
                'lead_id'     => $lead->id,
                'assigned_to' => $ownerUser->id,
                'error'       => $e->getMessage(),
            ]);
        }
    } else {

        \Log::warning('Lead assignment FCM notification skipped', [
            'lead_id'    => $lead->id,
            'assigned_to' => $userId,
            'reason'     => $ownerUser
                ? 'FCM token missing'
                : 'Lead unassigned',
        ]);
    }

    return redirect()
        ->route('dashboard')
        ->with(
            'success',
            $owner
                ? "Lead {$lead->reference} was assigned to {$owner}."
                : "Lead {$lead->reference} is now unassigned."
        );
}
}
