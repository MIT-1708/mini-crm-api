<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Query scoping will handle filtering reps' views.
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $user->isManager() || $lead->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $user->isManager() || $lead->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $user->isManager();
    }

    /**
     * Determine whether the user can assign the lead.
     */
    public function assign(User $user): bool
    {
        return $user->isManager();
    }

    /**
     * Determine whether the user can log activities for the lead.
     */
    public function logActivity(User $user, Lead $lead): bool
    {
        return $user->isManager() || $lead->assigned_to === $user->id;
    }
}
