<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        // User can view if they have access to any of the team's events
        return $this->canAccessAnyTeamEvent($user, $team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role !== UserRole::Judge;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::Admin && $this->canAccessAnyTeamEvent($user, $team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Check if user can access any of the team's events
     */
    private function canAccessAnyTeamEvent(User $user, Team $team): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        // Check if user has access to any of the team's events
        foreach ($team->events as $event) {
            if ($user->canAccessEvent($event)) {
                return true;
            }
        }

        return false;
    }
}
