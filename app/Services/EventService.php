<?php

namespace App\Services;

use App\Models\AwardTemplate;
use App\Models\Event;

class EventService
{
    /**
     * Initialize a new event with award templates
     */
    public function initializeEvent(Event $event, ?array $templateIds = null): void
    {
        $query = AwardTemplate::query()->where('is_active', true);

        if ($templateIds !== null) {
            $query->whereIn('id', $templateIds);
        }

        $templates = $query->orderBy('display_order')->get();

        foreach ($templates as $template) {
            $template->cloneForEvent($event);
        }
    }

    /**
     * Transition event to next status
     */
    public function transitionStatus(Event $event, string $newStatus): bool
    {
        $validTransitions = [
            'planning' => ['registration'],
            'registration' => ['judging'],
            'judging' => ['deliberation'],
            'deliberation' => ['completed'],
            'completed' => ['archived'],
        ];

        if (! isset($validTransitions[$event->status])) {
            return false;
        }

        if (! in_array($newStatus, $validTransitions[$event->status])) {
            return false;
        }

        $event->update(['status' => $newStatus]);

        return true;
    }
}
