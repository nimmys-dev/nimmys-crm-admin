<?php

namespace App\Policies;

use App\Models\CallDetail;
use App\Models\Lead;
use App\Models\User;

/**
 * Authorization for call logging.
 *
 * The rules, in one place:
 *
 *   Admin     everything.
 *   Manager   view, edit and delete calls on leads belonging to their shop.
 *   Employee  add calls and view history on leads assigned to them; edit
 *             only entries they logged themselves; never delete.
 *
 * Reachability of the lead is always checked first via LeadPolicy, so a user
 * who cannot see a lead can never see, add or touch its calls — this class
 * only narrows further, never widens.
 */
class CallDetailPolicy
{
    /**
     * Seeing the call history of a lead you can already see.
     */
    public function viewAny(User $user, Lead $lead): bool
    {
        return $user->can('view', $lead);
    }

    public function view(User $user, CallDetail $call): bool
    {
        if (! $user->can('view', $call->lead)) {
            return false;
        }

        return $this->withinReach($user, $call->lead);
    }

    /**
     * Logging a call is part of working a lead, so it follows update.
     */
    public function create(User $user, Lead $lead): bool
    {
        return $user->can('update', $lead) && $this->withinReach($user, $lead);
    }

    /**
     * Editing an entry.
     *
     * An Employee may correct their own entry but not one logged by someone
     * else, even on a lead they own — the history is a record of who said
     * what, and rewriting another person's note destroys that.
     */
    public function update(User $user, CallDetail $call): bool
    {
        if (! $user->can('update', $call->lead) || ! $this->withinReach($user, $call->lead)) {
            return false;
        }

        if ($user->can('leads.manage')) {
            return true;
        }

        return $call->wasLoggedBy($user);
    }

    /**
     * Deleting is reserved for leads.manage — Admin, and Manager within
     * their shop. An Employee never deletes call history, not even their own.
     */
    public function delete(User $user, CallDetail $call): bool
    {
        return $user->can('leads.manage')
            && $user->can('view', $call->lead)
            && $this->withinReach($user, $call->lead);
    }

    public function restore(User $user, CallDetail $call): bool
    {
        return $this->delete($user, $call);
    }

    /**
     * Manager reach is limited to their own shop.
     *
     * Admins are unrestricted. Employees are already constrained by lead
     * ownership, so shop is not applied to them a second time.
     *
     * A lead with no shop assigned stays reachable by every Manager —
     * otherwise unassigned leads would become permanently unmanageable.
     */
    private function withinReach(User $user, Lead $lead): bool
    {
        if ($user->isAdmin() || ! $user->isManager()) {
            return true;
        }

        if ($lead->shop_id === null) {
            return true;
        }

        $shopId = $user->managedShop?->id ?? $user->shop_id;

        return $shopId !== null && (int) $lead->shop_id === (int) $shopId;
    }
}
