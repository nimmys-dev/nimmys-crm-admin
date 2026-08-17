<?php

namespace App\Services;

use App\Enums\CallStatus;
use App\Enums\LeadStatus;
use App\Models\CallDetail;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadFollowUp;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class LeadActivityService
{
    /**
     * Record a new activity on a lead.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(
        Lead $lead,
        string $type,
        string $description,
        ?User $actor = null,
        array $properties = []
    ): LeadActivity {
        $actorId = $actor?->id ?? Auth::id();

        return $lead->activities()->create([
            'user_id' => $actorId,
            'activity_type' => $type,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    /**
     * Get all activities for a lead, newest first, eager-loading actor.
     *
     * @return Collection<int, LeadActivity>
     */
    public function getActivitiesForLead(Lead $lead): Collection
    {
        return $lead->activities()
            ->with('user:id,name,role,email')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Log initial lead creation.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function logCreated(Lead $lead, User $actor, array $attributes): LeadActivity
    {
        $assignedName = $lead->owner?->name ?? 'Unassigned';
        $sourceLabel = $lead->source?->label() ?? 'Direct';
        $statusLabel = $lead->status?->label() ?? 'New';

        $description = "Lead created by {$actor->name} from {$sourceLabel} with status {$statusLabel} (Assigned to: {$assignedName})";

        return $this->record($lead, 'created', $description, $actor, [
            'reference' => $lead->reference,
            'source' => $sourceLabel,
            'status' => $statusLabel,
            'assigned_to' => $assignedName,
            'phone' => $lead->phone,
            'email' => $lead->email,
        ]);
    }

    /**
     * Log lead updates.
     *
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>  $original
     */
    public function logUpdated(Lead $lead, User $actor, array $changes, array $original = []): ?LeadActivity
    {
        // Don't log if nothing meaningful changed (ignore timestamps)
        unset($changes['updated_at'], $changes['created_at']);

        if (empty($changes)) {
            return null;
        }

        $changeSummaries = [];
        $props = [];

        foreach ($changes as $key => $newVal) {
            $oldVal = $original[$key] ?? null;
            $fieldTitle = ucfirst(str_replace('_', ' ', $key));

            if ($key === 'status') {
                $oldStatus = $oldVal instanceof LeadStatus ? $oldVal->label() : (string) $oldVal;
                $newStatus = $newVal instanceof LeadStatus ? $newVal->label() : (string) $newVal;
                $changeSummaries[] = "Status changed from '{$oldStatus}' to '{$newStatus}'";
            } elseif ($key === 'assigned_to') {
                $oldUser = $oldVal ? User::find($oldVal)?->name : 'Unassigned';
                $newUser = $newVal ? User::find($newVal)?->name : 'Unassigned';
                $changeSummaries[] = "Assigned from '{$oldUser}' to '{$newUser}'";
            } else {
                $changeSummaries[] = "{$fieldTitle} updated";
            }

            $props[$key] = [
                'old' => $oldVal,
                'new' => $newVal,
            ];
        }

        $description = "Updated lead details: " . implode(', ', $changeSummaries);

        return $this->record($lead, 'updated', $description, $actor, $props);
    }

    /**
     * Log lead assignment / reassignment.
     */
    public function logAssigned(Lead $lead, User $actor, ?User $oldUser, ?User $newUser): LeadActivity
    {
        $oldName = $oldUser?->name ?? 'Unassigned';
        $newName = $newUser?->name ?? 'Unassigned';

        $description = "Reassigned lead from {$oldName} to {$newName}";

        return $this->record($lead, 'reassigned', $description, $actor, [
            'from_user_id' => $oldUser?->id,
            'from_user_name' => $oldName,
            'to_user_id' => $newUser?->id,
            'to_user_name' => $newName,
        ]);
    }

    /**
     * Log lead closure or status change.
     */
    public function logClosed(Lead $lead, User $actor, LeadStatus $status, ?string $reason = null): LeadActivity
    {
        $statusLabel = $status->label();
        $description = "Lead marked as {$statusLabel}";

        if ($reason) {
            $description .= " (Reason: {$reason})";
        }

        return $this->record($lead, 'closed', $description, $actor, [
            'status' => $statusLabel,
            'reason' => $reason,
        ]);
    }

    /**
     * Log call actions.
     */
    public function logCall(Lead $lead, User $actor, CallDetail $call, string $action = 'created'): LeadActivity
    {
        $type = match ($action) {
            'updated' => 'call_updated',
            'deleted' => 'call_deleted',
            default => 'call_logged',
        };

        if ($action === 'deleted') {
            $description = "Deleted call record from {$call->called_date?->format('d-M-Y')}";
            return $this->record($lead, $type, $description, $actor, [
                'call_id' => $call->id,
            ]);
        }

        if ($action === 'updated') {
            $description = "Updated call record from {$call->called_date?->format('d-M-Y')}";
            return $this->record($lead, $type, $description, $actor, [
                'call_id' => $call->id,
                'status' => $call->call_status?->label(),
            ]);
        }

        // Newly logged call
        $status = $call->call_status;
        $props = [
            'call_id' => $call->id,
            'call_status' => $status?->label(),
            'called_date' => $call->called_date?->format('d-M-Y'),
            'called_time' => $call->called_time,
            'remarks' => strip_tags((string) $call->remarks),
        ];

        if ($call->isNotAnswered()) {
            $followUp = $call->next_followup_date?->format('d-M-Y') ?? 'None';
            $description = "Logged call: Not Answered (Next follow-up: {$followUp})";
            $props['next_followup_date'] = $followUp;
        } else {
            if ($call->interest === false) {
                $description = "Logged call: Answered — Not Interested (Reason: {$call->reason})";
                $props['interest'] = false;
                $props['reason'] = $call->reason;
            } elseif ($call->interest === true) {
                $props['interest'] = true;
                if ($call->is_item_sold === true) {
                    $inv = $call->invoice_number ? "Invoice #{$call->invoice_number}" : "Item Sold";
                    $description = "Logged call: Answered — Interested & Item Sold ({$inv})";
                    $props['is_item_sold'] = true;
                    $props['invoice_number'] = $call->invoice_number;
                    $props['invoice_file_path'] = $call->invoice_file_path;
                } else {
                    $followUp = $call->next_followup_date?->format('d-M-Y') ?? 'None';
                    $description = "Logged call: Answered — Interested & Under Follow-up (Next follow-up: {$followUp})";
                    $props['is_item_sold'] = false;
                    $props['next_followup_date'] = $followUp;
                }
            } else {
                $description = "Logged call: Answered";
            }
        }

        return $this->record($lead, $type, $description, $actor, $props);
    }

    /**
     * Log quotation actions.
     */
    public function logQuotation(Lead $lead, User $actor, Quotation $quotation, string $action = 'created'): LeadActivity
    {
        $type = match ($action) {
            'updated' => 'quotation_updated',
            'downloaded' => 'quotation_downloaded',
            default => 'quotation_created',
        };

        $totalFormatted = '₹' . number_format($quotation->total_amount ?? 0, 2);

        if ($action === 'downloaded') {
            $description = "Downloaded PDF for Quotation #{$quotation->quotation_number}";
        } elseif ($action === 'updated') {
            $description = "Updated Quotation #{$quotation->quotation_number} for {$totalFormatted}";
        } else {
            $itemCount = $quotation->items()->count();
            $description = "Created Quotation #{$quotation->quotation_number} for {$totalFormatted} ({$itemCount} items)";
        }

        return $this->record($lead, $type, $description, $actor, [
            'quotation_id' => $quotation->id,
            'quotation_number' => $quotation->quotation_number,
            'total_amount' => $quotation->total_amount,
        ]);
    }

    /**
     * Log follow-up scheduling.
     */
    public function logFollowUp(Lead $lead, User $actor, LeadFollowUp $followUp): LeadActivity
    {
        $scheduled = $followUp->scheduled_at?->format('d-M-Y g:i A') ?? 'Unscheduled';
        $typeLabel = $followUp->type?->label() ?? 'Follow-up';

        $description = "Scheduled {$typeLabel} for {$scheduled}";

        return $this->record($lead, 'followup_created', $description, $actor, [
            'followup_id' => $followUp->id,
            'type' => $typeLabel,
            'scheduled_at' => $scheduled,
            'notes' => $followUp->notes,
        ]);
    }
}
