<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Award>
 */
class AwardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Award',
            'code' => fake()->unique()->lexify('???-award'),
            'description' => fake()->sentence(),
            'is_ranked' => true,
            'is_hierarchical' => false,
            'is_locked' => false,
            'is_finalized' => false,
        ];
    }
}
