<?php

namespace App\Http\Resources;

use App\Models\CallDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape of a call record for the mobile API.
 *
 * Not yet routed. The endpoints will be thin: CallDetailService already
 * exposes createCall, updateCall, deleteCall, getLeadTimeline and
 * getUpcomingFollowups, each taking the acting user and inheriting the same
 * visibility scoping the web enforces. Wiring them up is a routes + resource
 * exercise, with no refactoring of this module.
 *
 * Enums are sent as { value, label } so the client renders the label without
 * holding its own copy of the mapping.
 *
 * @mixin CallDetail
 */
class CallDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $urgency = $this->followUpUrgency();

        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,

            'call_status' => [
                'value' => $this->call_status->value,
                'label' => $this->call_status->label(),
                'reached_contact' => $this->call_status->reachedContact(),
                'is_terminal' => $this->call_status->isTerminal(),
            ],

            'remarks' => $this->remarks,

            // Kept as separate fields to match the stored shape, with a
            // combined ISO timestamp so clients need not reassemble them.
            'called_date' => $this->called_date->toDateString(),
            'called_time' => $this->called_time,
            'called_at' => $this->calledAt()->toIso8601String(),

            'duration' => $this->duration,
            'duration_for_humans' => $this->durationForHumans(),

            'next_followup_date' => $this->next_followup_date?->toDateString(),
            'follow_up' => $urgency ? [
                'urgency' => $urgency->value,
                'label' => $urgency->label(),
            ] : null,

            // whenLoaded keeps the payload honest: a relation that was not
            // eager loaded is omitted rather than triggering a lazy query.
            'caller' => $this->whenLoaded('caller', fn () => [
                'id' => $this->caller?->id,
                'name' => $this->caller?->name,
            ]),

            'lead' => $this->whenLoaded('lead', fn () => [
                'id' => $this->lead->id,
                'reference' => $this->lead->reference,
                'name' => $this->lead->name,
            ]),

            // Lets the client hide controls the server would refuse anyway.
            'permissions' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
