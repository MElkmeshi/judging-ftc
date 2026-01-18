<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Championship',
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'description' => fake()->sentence(),
            'event_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
            'location' => fake()->city().', '.fake()->stateAbbr(),
            'status' => 'planning',
            'is_active' => true,
        ];
    }
}
