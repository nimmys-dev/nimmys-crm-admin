<?php

namespace App\Services;

use App\Contracts\LeadRepository;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadFollowUp;
use App\Models\User;
use App\Support\LeadReference;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for leads.
 *
 * The repository knows how to read and write; this class knows what those
 * operations mean — allocating a reference, stamping closure, keeping the
 * denormalised follow-up date honest, and recording who did what.
 */
class LeadService
{
    public function __construct(private readonly LeadRepository $leads) {}

    /**
     * Create a lead, allocating its reference inside the same transaction so
     * two concurrent submissions cannot claim the same number.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, User $actor): Lead
    {
        return LeadReference::withNext(function (string $reference) use ($attributes, $actor) {
            $attributes['reference'] = $reference;
            $attributes['created_by'] = $actor->id;

            // An Employee creating a lead owns it. Leaving it unassigned
            // would make it invisible to them the moment it was saved.
            if (blank($attributes['assigned_to'] ?? null) && ! $actor->can('leads.assign')) {
                $attributes['assigned_to'] = $actor->id;
            }

            return $this->leads->create($this->withClosureState($attributes, null));
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lead $lead, array $attributes, User $actor): Lead
    {
        return $this->leads->update($lead, $this->withClosureState($attributes, $lead));
    }

    public function delete(Lead $lead): void
    {
        $this->leads->delete($lead);
    }

    /**
     * Reassign ownership.
     *
     * Kept separate from update() because it is a distinct permission
     * (leads.assign) and a distinct audit event.
     */
    public function assign(Lead $lead, ?int $userId): Lead
    {
        return $this->leads->update($lead, ['assigned_to' => $userId]);
    }

    /**
     * Close a lead as Won or Lost.
     *
     * Kept separate from update() because it is a narrower, distinct action
     * offered straight from the detail page — only the outcome and, for a
     * loss, the reason, rather than the whole lead form. Closure bookkeeping
     * (closed_at, clearing a stale lost_reason) is still handled the same
     * way as a full update, via withClosureState().
     */
    public function close(Lead $lead, LeadStatus $status, ?string $reason = null): Lead
    {
        return $this->leads->update($lead, $this->withClosureState([
            'status' => $status,
            'lost_reason' => $reason,
        ], $lead));
    }

    /*
    |--------------------------------------------------------------------------
    | Follow-ups
    |--------------------------------------------------------------------------
    */

    /**
     * Log a follow-up against a lead.
     *
     * Wrapped in a transaction because it touches two tables: the follow-up
     * row and the lead's denormalised next_follow_up_at / last_contacted_at.
     * A partial write here would leave the dashboard lying.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function addFollowUp(Lead $lead, array $attributes, User $actor): LeadFollowUp
    {
        return DB::transaction(function () use ($lead, $attributes, $actor) {
            $followUp = $lead->followUps()->create([
                ...$attributes,
                'user_id' => $actor->id,
            ]);

            // Logging a completed contact is itself contact.
            if ($followUp->isComplete()) {
                $lead->forceFill(['last_contacted_at' => $followUp->completed_at])->saveQuietly();
            }

            $this->syncNextFollowUp($lead);

            return $followUp;
        });
    }

    public function completeFollowUp(LeadFollowUp $followUp, ?string $outcome = null): LeadFollowUp
    {
        return DB::transaction(function () use ($followUp, $outcome) {
            $followUp->update([
                'completed_at' => now(),
                'outcome' => $outcome ?: $followUp->outcome,
            ]);

            $followUp->lead->forceFill(['last_contacted_at' => now()])->saveQuietly();

            $this->syncNextFollowUp($followUp->lead);

            return $followUp->refresh();
        });
    }

    /**
     * Recompute leads.next_follow_up_at from the earliest open follow-up.
     *
     * The column is denormalised so "what is due" is an indexed lookup rather
     * than a correlated subquery on every dashboard render. That only holds
     * if it is recomputed on every change, which is why every mutation path
     * routes through here.
     */
    public function syncNextFollowUp(Lead $lead): void
    {
        $next = $lead->followUps()
            ->open()
            ->whereNotNull('scheduled_at')
            ->min('scheduled_at');

        $lead->forceFill([
            'next_follow_up_at' => $next ? Carbon::parse($next)->toDateString() : null,
        ])->saveQuietly();
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Keep closure bookkeeping consistent with the status.
     *
     * Moving to Won or Lost stamps closed_at and keeps whatever reason came
     * with it — Won and Lost are both decisions worth a one-line record of
     * why, not just Lost. Reopening clears both, so a reopened lead never
     * carries a stale explanation.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withClosureState(array $attributes, ?Lead $existing): array
    {
        if (! array_key_exists('status', $attributes)) {
            return $attributes;
        }

        $status = $attributes['status'] instanceof LeadStatus
            ? $attributes['status']
            : LeadStatus::from((string) $attributes['status']);

        if ($status->isClosed()) {
            $attributes['closed_at'] = $existing?->closed_at ?? now();

            return $attributes;
        }

        $attributes['closed_at'] = null;
        $attributes['lost_reason'] = null;

        return $attributes;
    }
}
