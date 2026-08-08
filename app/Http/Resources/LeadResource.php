<?php

namespace App\Http\Resources;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape of a lead for the mobile API.
 *
 * Written now so the API and the web UI cannot drift on what a lead looks
 * like. Not yet routed — the endpoints land with the mobile app, and they
 * will read through LeadRepository, inheriting the same visibility scoping
 * the web enforces.
 *
 * Enums are sent as { value, label } pairs so the client renders the label
 * without having to hold its own copy of the mapping.
 *
 * @mixin Lead
 */
class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,

            'name' => $this->name,
            'company' => $this->company,
            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'city' => $this->city,

            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'is_open' => $this->status->isOpen(),
            ],
            'priority' => [
                'value' => $this->priority->value,
                'label' => $this->priority->label(),
            ],
            'source' => $this->when($this->source !== null, fn () => [
                'value' => $this->source->value,
                'label' => $this->source->label(),
            ]),

            'value' => $this->value,
            'description' => $this->description,
            'lost_reason' => $this->lost_reason,

            'next_follow_up_at' => $this->next_follow_up_at?->toDateString(),
            'is_overdue' => $this->isOverdue(),
            'days_until_follow_up' => $this->daysUntilFollowUp(),
            'last_contacted_at' => $this->last_contacted_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),

            // whenLoaded keeps the payload honest: a relation that was not
            // eager loaded is omitted rather than triggering a lazy query.
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'shop' => $this->whenLoaded('shop', fn () => [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
            ]),
            'follow_ups' => LeadFollowUpResource::collection($this->whenLoaded('followUps')),
            'open_follow_ups_count' => $this->whenCounted('openFollowUps'),

            // Lets the client hide controls the server would refuse anyway.
            'permissions' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'assign' => $request->user()?->can('assign', $this->resource) ?? false,
            ],

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
