<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/**
 * Record-level authorization for leads.
 *
 * Delegates to the `leads.*` gates defined in AuthServiceProvider so the
 * rules have one home: this class exists to give the standard verbs
 * ($user->can('update', $lead), authorizeResource, API policies) without
 * restating the logic.
 *
 * The rule in one line: anyone with Lead module access can read and edit the
 * whole pipeline; deleting and reassigning remain restricted to leads.manage.
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.access');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('leads.view', $lead);
    }

    public function create(User $user): bool
    {
        return $user->can('leads.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('leads.update', $lead);
    }

    /**
     * Deleting is reserved for leads.manage — an Employee must not be able to
     * erase pipeline history, even for their own lead.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete');
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->can('leads.assign');
    }

    /**
     * Adding a follow-up is part of working a lead, so it follows update.
     */
    public function addFollowUp(User $user, Lead $lead): bool
    {
        return $user->can('leads.update', $lead);
    }
}
