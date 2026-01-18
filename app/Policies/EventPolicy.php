<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view events they're assigned to
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Event $event): bool
    {
        return $user->canAccessEvent($event);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        // Admins can update events they're assigned to
        return $user->role === UserRole::Admin && $user->canAccessEvent($event);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }

    /**
     * Determine whether the user can manage judges for an event.
     */
    public function manageJudges(User $user, Event $event): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        return $user->role === UserRole::Admin && $user->canAccessEvent($event);
    }

    /**
     * Determine whether the user can view deliberation dashboard for an event.
     */
    public function viewDeliberation(User $user, Event $event): bool
    {
        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::Admin && $user->canAccessEvent($event)) {
            return $user->events()
                ->where('events.id', $event->id)
                ->wherePivot('can_deliberate', true)
                ->exists();
        }

        return false;
    }
}
