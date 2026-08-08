<?php

namespace App\Contracts;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Data access for leads.
 *
 * A repository earns its place here for one specific reason: every read must
 * be narrowed to what the viewer is allowed to see. Centralising that in
 * `visibleTo()` means no controller, service or Blade template can
 * accidentally issue an unscoped query — the rule has exactly one home.
 *
 * The web controllers and the future mobile API both depend on this
 * interface, so scoping cannot diverge between the two surfaces.
 */
interface LeadRepository
{
    /**
     * Base query narrowed to what this viewer may see.
     *
     * Exposed so callers that need a custom query still inherit the scoping
     * rather than reaching for Lead::query() and losing it.
     *
     * @return Builder<Lead>
     */
    public function visibleTo(User $viewer): Builder;

    /**
     * Paginated listing, already narrowed to the viewer.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Lead>
     */
    public function paginate(User $viewer, array $filters): LengthAwarePaginator;

    /**
     * @return Collection<int, Lead>
     */
    public function dueForFollowUp(User $viewer, int $withinDays = 0, int $limit = 5): Collection;

    /**
     * Counts per status for the viewer's visible leads.
     *
     * @return array<string, int>
     */
    public function statusCounts(User $viewer): array;

    /**
     * Headline figures for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function statistics(User $viewer): array;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Lead;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lead $lead, array $attributes): Lead;

    public function delete(Lead $lead): void;
}
