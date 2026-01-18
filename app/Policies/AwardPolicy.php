<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Award;
use App\Models\User;

class AwardPolicy
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
    public function view(User $user, Award $award): bool
    {
        return $user->canAccessEvent($award->event);
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
    public function update(User $user, Award $award): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::Admin && $user->canAccessEvent($award->event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Award $award): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Award $award): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Award $award): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can score teams for this award.
     * CRITICAL FTC COMPLIANCE: Only assigned judges can score, only when unlocked and in judging phase.
     */
    public function score(User $user, Award $award): bool
    {
        if (! $award->canBeScored()) {
            return false;
        }

        if (! $user->canAccessEvent($award->event)) {
            return false;
        }

        // Check if user is assigned to this award as a judge
        return $award->judges()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view scores for this award.
     * CRITICAL FTC COMPLIANCE: Judges see only their own scores, admins see all.
     */
    public function viewScores(User $user, Award $award): bool
    {
        // Judges can only view their own scores
        if ($user->role === UserRole::Judge) {
            return $award->judges()->where('users.id', $user->id)->exists();
        }

        // Admins can view all scores for deliberation
        return $user->role !== UserRole::Judge && $user->canAccessEvent($award->event);
    }

    /**
     * Determine whether the user can deliberate and assign this award.
     * CRITICAL FTC COMPLIANCE: Only admins with deliberation permission.
     */
    public function deliberate(User $user, Award $award): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::Admin) {
            return $user->events()
                ->where('events.id', $award->event_id)
                ->wherePivot('can_deliberate', true)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can finalize award assignments.
     * CRITICAL FTC COMPLIANCE: Only admins with deliberation permission can finalize.
     */
    public function finalize(User $user, Award $award): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::Admin) {
            return $user->events()
                ->where('events.id', $award->event_id)
                ->wherePivot('can_deliberate', true)
                ->exists();
        }

        return false;
    }
}
