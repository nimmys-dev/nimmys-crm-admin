<?php

namespace App\Services;

use App\Contracts\LeadRepository;
use App\Enums\CallStatus;
use App\Models\CallDetail;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Business rules for call logging.
 *
 * Visibility is inherited rather than reimplemented: every read starts from
 * LeadRepository::visibleTo(), so an Employee can only ever reach calls on
 * leads assigned to them.
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
     * @param  array<string, mixed>  $attributes
     */
    public function createCall(Lead $lead, array $attributes, User $actor, ?UploadedFile $invoiceFile = null): CallDetail
    {
        $call = DB::transaction(function () use ($lead, $attributes, $actor, $invoiceFile) {
            if ($invoiceFile) {
                $attributes['invoice_file_path'] = $invoiceFile->store('invoices', 'public');
            }

            $call = $lead->callDetails()->create([
                ...$attributes,
                'called_date' => $attributes['called_date'] ?? today()->toDateString(),
                'called_time' => $attributes['called_time'] ?? now()->format('H:i'),
                // Falls back to the actor so the column is never orphaned;
                // only users who may reassign can name someone else.
                'called_by' => $attributes['called_by'] ?? $actor->id,
            ]);

            $this->touchLastContacted($lead, $call);

            return $call;
        });

        app(LeadActivityService::class)->logCall($lead, $actor, $call, 'created');

        return $call;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateCall(CallDetail $call, array $attributes, ?UploadedFile $invoiceFile = null, ?User $actor = null): CallDetail
    {
        $updatedCall = DB::transaction(function () use ($call, $attributes, $invoiceFile) {
            if ($invoiceFile) {
                // Delete old file if present
                if ($call->invoice_file_path) {
                    Storage::disk('public')->delete($call->invoice_file_path);
                }
                $attributes['invoice_file_path'] = $invoiceFile->store('invoices', 'public');
            } elseif (array_key_exists('is_item_sold', $attributes) && empty($attributes['is_item_sold'])) {
                // If item is not sold, clear invoice file if previously stored
                if ($call->invoice_file_path) {
                    Storage::disk('public')->delete($call->invoice_file_path);
                    $attributes['invoice_file_path'] = null;
                }
            }

            $call->update($attributes);

            $this->touchLastContacted($call->lead, $call->refresh());

            return $call;
        });

        $effectiveActor = $actor ?? Auth::user() ?? User::first();
        if ($effectiveActor) {
            app(LeadActivityService::class)->logCall($updatedCall->lead, $effectiveActor, $updatedCall, 'updated');
        }

        return $updatedCall;
    }

    public function deleteCall(CallDetail $call, ?User $actor = null): void
    {
        if ($call->invoice_file_path) {
            Storage::disk('public')->delete($call->invoice_file_path);
        }

        $effectiveActor = $actor ?? Auth::user() ?? User::first();
        if ($effectiveActor) {
            app(LeadActivityService::class)->logCall($call->lead, $effectiveActor, $call, 'deleted');
        }

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
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<CallDetail>
     */
    public function paginateForLead(Lead $lead, array $filters = [], int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return $lead->callDetails()
            ->with(['caller:id,name', 'lead' => $this->leadColumnsForPolicy()])
            ->search($filters['q'] ?? null)
            ->status($filters['call_status'] ?? null)
            ->calledBy($filters['called_by'] ?? null)
            ->sorted($filters['sort'] ?? null, $filters['direction'] ?? null)
            ->paginate($perPage, ['*'], 'call_page')
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
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
     * Distinct leads with interest shown.
     */
    public function interestedLeads(User $viewer): int
    {
        return $this->visibleCalls($viewer)
            ->where('interest', true)
            ->distinct()
            ->count('lead_id');
    }

    public function convertedLeads(User $viewer): int
    {
        return $this->visibleCalls($viewer)
            ->where('is_item_sold', true)
            ->distinct()
            ->count('lead_id');
    }

    public function pendingFollowups(User $viewer, int $withinDays = 0): int
    {
        return $this->visibleCalls($viewer)->pendingFollowUp($withinDays)->count();
    }

    /**
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
     * Calls scoped to leads the viewer is permitted to see.
     *
     * @return Builder<CallDetail>
     */
    private function visibleCalls(User $viewer): Builder
    {
        return CallDetail::query()
            ->whereIn('lead_id', $this->leads->visibleTo($viewer)->select('leads.id'));
    }

    /**
     * Minimal lead columns to satisfy policies without loading full leads.
     *
     * @param  list<string>  $extra
     */
    private function leadColumnsForPolicy(array $extra = []): \Closure
    {
        $columns = array_unique(['id', 'assigned_to', 'shop_id', ...$extra]);

        return fn ($query) => $query->select($columns);
    }

    /**
     * A call that reached the person counts as contact.
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
