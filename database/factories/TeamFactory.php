<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_number' => fake()->unique()->numberBetween(1000, 99999),
            'team_name' => fake()->company().' Robotics',
            'school_organization' => fake()->company().' School',
            'city' => fake()->city(),
            'state_province' => fake()->stateAbbr(),
            'country' => 'USA',
            'is_rookie' => fake()->boolean(20), // 20% chance of being rookie
        ];
    }

    /**
     * Attach team to an event after creation
     */
    public function forEvent(Event $event, bool $isActive = true): static
    {
        return $this->afterCreating(function ($team) use ($event, $isActive) {
            $team->events()->attach($event->id, ['is_active' => $isActive]);
        });
    }

    /**
     * Mark the team as a rookie.
     */
    public function rookie(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_rookie' => true,
        ]);
    }
}
