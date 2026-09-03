<?php

namespace App\Repositories;

use App\Contracts\LeadRepository;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentLeadRepository implements LeadRepository
{
    public const PER_PAGE = 15;

    /**
     * Every read starts here.
     *
     * An Employee sees only the leads assigned to them. Anyone holding
     * leads.manage (Admin, Manager) sees the full pipeline. Because this is
     * the only entry point to lead data, an Employee cannot reach another
     * person's lead through any filter, sort or page number.
     *
     * @return Builder<Lead>
     */
    public function visibleTo(User $viewer): Builder
    {
        $query = Lead::query();

        if (! $viewer->can('leads.manage')) {
            $query->where('assigned_to', $viewer->id);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Lead>
     */
    public function paginate(User $viewer, array $filters): LengthAwarePaginator
    {
        return $this->visibleTo($viewer)
            // Eager loaded so the listing stays at a fixed query count
            // regardless of page size.
            ->with(['owner:id,name', 'shop:id,name', 'latestCall'])
            ->withCount(['openFollowUps'])
            ->search($filters['q'] ?? null)
            ->status($filters['status'] ?? null)
            ->priority($filters['priority'] ?? null)
            ->source($filters['source'] ?? null)
            ->assignedTo($filters['assigned_to'] ?? null)
            ->forShop($filters['shop_id'] ?? null)
            ->when(
                ($filters['due'] ?? null) === 'overdue',
                fn (Builder $query) => $query->dueForFollowUp()
            )
            ->sorted($filters['sort'] ?? null, $filters['direction'] ?? null)
            ->paginate($filters['per_page'] ?? self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return Collection<int, Lead>
     */
    public function dueForFollowUp(User $viewer, int $withinDays = 0, int $limit = 5): Collection
    {
        return $this->visibleTo($viewer)
            ->with(['owner:id,name'])
            ->dueForFollowUp($withinDays)
            ->orderBy('next_follow_up_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Counts per status in one grouped query rather than one per status.
     *
     * @return array<string, int>
     */
    public function statusCounts(User $viewer): array
    {
        $counts = $this->visibleTo($viewer)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Zero-fill so a status missing from the data still renders.
        return collect(LeadStatus::cases())
            ->mapWithKeys(fn (LeadStatus $status) => [
                $status->value => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function statistics(User $viewer): array
    {
        $byStatus = $this->statusCounts($viewer);

        $open = collect(LeadStatus::open())
            ->sum(fn (LeadStatus $status) => $byStatus[$status->value]);

        // $won = $byStatus[LeadStatus::Won->value];
        // $lost = $byStatus[LeadStatus::Lost->value];
        // $closed = $won + $lost;

        // Aggregates in a single pass rather than three separate queries.
        // $totals = $this->visibleTo($viewer)
        //     ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN status = ? THEN value ELSE 0 END) AS won_value', [LeadStatus::Won->value])
        //     ->first();

        return [
            'total' => (int) ($totals->total ?? 0),
            'by_status' => $byStatus,
            'open' => $open,
            // 'won' => $won,
            // 'lost' => $lost,
            // 'won_value' => (float) ($totals->won_value ?? 0),

            // Share of *decided* leads that were won. Measuring against every
            // lead would drag the rate down simply because the pipeline is
            // full, which is the opposite of the signal wanted.
            // 'conversion_rate' => $closed > 0 ? round($won / $closed * 100, 1) : null,

            'due_today' => $this->visibleTo($viewer)->dueForFollowUp()->count(),
            'overdue' => $this->visibleTo($viewer)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', today())
                ->open()
                ->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Lead
    {
        return Lead::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lead $lead, array $attributes): Lead
    {
        $lead->update($attributes);

        return $lead->refresh();
    }

    public function delete(Lead $lead): void
    {
        $lead->delete();
    }
}
