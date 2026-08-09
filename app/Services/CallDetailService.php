<?php

namespace App\Services;

use App\Contracts\LeadRepository;
use App\Enums\CallStatus;
use App\Models\CallDetail;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for call logging.
 *
 * Visibility is inherited rather than reimplemented: every read starts from
 * LeadRepository::visibleTo(), so an Employee can only ever reach calls on
 * leads assigned to them. Restating that rule here would give it a second
 * home and a chance to drift.
 */
class CallDetailService
{
    public const PER_PAGE = 10;

    public function __construct(private readonly LeadRepository $leads) {}

    /*
    |--------------------------------------------------------------------------
    | Writes
    |--------------------------------------------------------------------------
    */

    /**
     * Log a call against a lead.
     *
     * Transactional because a connected call also advances the lead's
     * last_contacted_at — a partial write would leave the lead looking
     * un-contacted while its own history says otherwise.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createCall(Lead $lead, array $attributes, User $actor): CallDetail
    {
        return DB::transaction(function () use ($lead, $attributes, $actor) {
            $call = $lead->callDetails()->create([
                ...$attributes,
                // Falls back to the actor so the column is never orphaned;
                // only users who may reassign can name someone else.
                'called_by' => $attributes['called_by'] ?? $actor->id,
            ]);

            $this->touchLastContacted($lead, $call);

            return $call;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateCall(CallDetail $call, array $attributes): CallDetail
    {
        return DB::transaction(function () use ($call, $attributes) {
            $call->update($attributes);

            $this->touchLastContacted($call->lead, $call->refresh());

            return $call;
        });
    }

    public function deleteCall(CallDetail $call): void
    {
        $call->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Reads
    |--------------------------------------------------------------------------
    */

    /**
     * Full call history for a lead, newest first.
     *
     * @return Collection<int, CallDetail>
     */
    public function getLeadTimeline(Lead $lead, int $limit = 20): Collection
    {
        return $lead->callDetails()
            ->with(['caller:id,name', 'lead' => $this->leadColumnsForPolicy()])
            ->limit($limit)
            ->get();
    }

    /**
     * Paginated, searchable call history for the lead detail page.
     *
     * Uses its own page name so it cannot collide with another paginator on
     * the same page.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<CallDetail>
     */
    public function paginateForLead(Lead $lead, array $filters = []): LengthAwarePaginator
    {
        return $lead->callDetails()
            ->with(['caller:id,name', 'lead' => $this->leadColumnsForPolicy()])
            ->search($filters['q'] ?? null)
            ->status($filters['call_status'] ?? null)
            ->sorted($filters['sort'] ?? null, $filters['direction'] ?? null)
            ->paginate(
                $filters['per_page'] ?? self::PER_PAGE,
                ['*'],
                'calls'
            )
            ->withQueryString();
    }

    /**
     * Calls with a follow-up due on or before $withinDays from today,
     * narrowed to what the viewer may see.
     *
     * This is the query the future scheduler will call. It is deliberately
     * a plain read with no side effects, so a command, a queued job or an
     * API endpoint can all use it unchanged.
     *
     * @return Collection<int, CallDetail>
     */
    public function getUpcomingFollowups(User $viewer, int $withinDays = 0, int $limit = 20): Collection
    {
        return $this->visibleCalls($viewer)
            ->with(['caller:id,name', 'lead' => $this->leadColumnsForPolicy(['reference', 'name'])])
            ->pendingFollowUp($withinDays)
            ->orderBy('next_followup_date')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard statistics — prepared, not yet surfaced
    |--------------------------------------------------------------------------
    |
    | Widgets are deliberately not built. These are the exact figures the
    | dashboard will ask for, scoped to the viewer, so wiring them up later
    | is a view change rather than a query-writing exercise.
    */

    public function totalCalls(User $viewer): int
    {
        return $this->visibleCalls($viewer)->count();
    }

    public function todaysCalls(User $viewer): int
    {
        return $this->visibleCalls($viewer)->onDate(today())->count();
    }

    /**
     * Distinct leads whose most recent call was a positive outcome.
     *
     * Counting calls would double-count a lead called three times; the
     * question is how many leads are interested, not how many calls went well.
     */
    public function interestedLeads(User $viewer): int
    {
        return $this->visibleCalls($viewer)
            ->whereIn('call_status', [CallStatus::Interested->value])
            ->distinct()
            ->count('lead_id');
    }

    public function convertedLeads(User $viewer): int
    {
        return $this->visibleCalls($viewer)
            ->where('call_status', CallStatus::Converted->value)
            ->distinct()
            ->count('lead_id');
    }

    public function pendingFollowups(User $viewer, int $withinDays = 0): int
    {
        return $this->visibleCalls($viewer)->pendingFollowUp($withinDays)->count();
    }

    /**
     * Every prepared figure in one call, for when the widget is built.
     *
     * @return array<string, int>
     */
    public function statistics(User $viewer): array
    {
        return [
            'total_calls' => $this->totalCalls($viewer),
            'todays_calls' => $this->todaysCalls($viewer),
            'interested_leads' => $this->interestedLeads($viewer),
            'converted_leads' => $this->convertedLeads($viewer),
            'pending_followups' => $this->pendingFollowups($viewer),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Calls on leads this viewer is allowed to see.
     *
     * whereIn over the lead repository's scoped query, so Employee ownership
     * and Manager reach are both inherited from the Lead module.
     *
     * @return Builder<CallDetail>
     */
    public function visibleCalls(User $viewer): Builder
    {
        return CallDetail::query()->whereIn(
            'lead_id',
            $this->leads->visibleTo($viewer)->select('leads.id')
        );
    }

    /**
     * Eager-load constraint for the parent lead.
     *
     * CallDetailPolicy reads the lead on every @can check, so it must be
     * loaded or preventLazyLoading rejects the render.
     *
     * assigned_to and shop_id are mandatory: the policy decides Employee
     * ownership from the first and Manager reach from the second. Selecting
     * a narrower set would silently hand every Manager access to every shop,
     * because a missing shop_id reads as null — which the policy treats as
     * "unassigned, therefore reachable".
     *
     * @param  array<int, string>  $extra
     */
    private function leadColumnsForPolicy(array $extra = []): \Closure
    {
        $columns = array_unique(['id', 'assigned_to', 'shop_id', ...$extra]);

        return fn ($query) => $query->select($columns);
    }

    /**
     * A call that reached the person counts as contact.
     *
     * Only moves the timestamp forward — editing an old call must not drag
     * last_contacted_at backwards past a more recent one.
     */
    private function touchLastContacted(Lead $lead, CallDetail $call): void
    {
        if (! $call->call_status->reachedContact()) {
            return;
        }

        $calledAt = $call->calledAt();

        if ($lead->last_contacted_at !== null && $lead->last_contacted_at->gte($calledAt)) {
            return;
        }

        $lead->forceFill(['last_contacted_at' => $calledAt])->saveQuietly();
    }
}
