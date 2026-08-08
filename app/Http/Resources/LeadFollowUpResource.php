<?php

namespace App\Http\Resources;

use App\Models\LeadFollowUp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LeadFollowUp
 */
class LeadFollowUpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,

            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
                'icon' => $this->type->icon(),
            ],

            'notes' => $this->notes,
            'outcome' => $this->outcome,

            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'is_complete' => $this->isComplete(),
            'is_overdue' => $this->isOverdue(),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
