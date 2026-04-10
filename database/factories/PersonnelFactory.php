<?php

namespace Database\Factories;

use App\Models\Personnel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Personnel>
 */
class PersonnelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'custom_id' => fake()->unique()->bothify('EMP-####'),
            'name' => fake()->name(),
            'person_type' => 0,
            'gender' => fake()->randomElement([0, 1]),
            'birthday' => fake()->optional()->date(),
            'id_card' => fake()->optional()->numerify('##########'),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->address(),
            'photo_path' => null,
            'photo_hash' => null,
        ];
    }

    /** Indicate that the personnel is on the block list. */
    public function blockList(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 1,
        ]);
    }

    /** Indicate that the personnel has a photo. */
    public function withPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo_path' => 'personnel/test-photo.jpg',
            'photo_hash' => md5('test'),
        ]);
    }
}
