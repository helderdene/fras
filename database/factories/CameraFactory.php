<?php

namespace Database\Factories;

use App\Models\Camera;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Camera>
 */
class CameraFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'name' => fake()->randomElement(['Main Entrance', 'Back Gate', 'Lobby', 'Parking Lot', 'Server Room', 'Reception']),
            'location_label' => fake()->sentence(3),
            'latitude' => fake()->latitude(8.93, 8.97),
            'longitude' => fake()->longitude(125.52, 125.57),
            'is_online' => false,
            'last_seen_at' => null,
        ];
    }

    /** Indicate that the camera is online. */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }

    /** Indicate that the camera is offline. */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_online' => false,
        ]);
    }
}
