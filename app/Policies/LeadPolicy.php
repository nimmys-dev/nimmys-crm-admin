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
 * The rule in one line: anyone with leads.manage handles the whole pipeline;
 * an Employee with lead_module_access handles only what is assigned to them,
 * and can never delete or reassign.
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
