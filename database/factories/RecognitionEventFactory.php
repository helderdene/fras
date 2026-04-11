<?php

namespace Database\Factories;

use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecognitionEvent>
 */
class RecognitionEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'camera_id' => Camera::factory(),
            'personnel_id' => Personnel::factory(),
            'custom_id' => fake()->unique()->bothify('EMP-####'),
            'camera_person_id' => fake()->optional()->uuid(),
            'record_id' => fake()->randomNumber(8),
            'verify_status' => 1,
            'person_type' => 0,
            'similarity' => fake()->randomFloat(1, 70, 99),
            'is_real_time' => true,
            'name_from_camera' => fake()->optional()->name(),
            'facesluice_id' => null,
            'id_card' => null,
            'phone' => null,
            'is_no_mask' => 0,
            'target_bbox' => null,
            'captured_at' => now(),
            'face_image_path' => null,
            'scene_image_path' => null,
            'raw_payload' => ['operator' => 'RecPush', 'info' => []],
            'severity' => 'info',
        ];
    }

    /** Set severity to critical (block-list match). */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 1,
            'verify_status' => 0,
            'severity' => 'critical',
        ]);
    }

    /** Set severity to warning (refused entry). */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 0,
            'verify_status' => 2,
            'severity' => 'warning',
        ]);
    }

    /** Set severity to info (allow-list match). */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 0,
            'verify_status' => 1,
            'severity' => 'info',
        ]);
    }

    /** Set severity to ignored (stranger/not registered). */
    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 0,
            'verify_status' => 3,
            'severity' => 'ignored',
        ]);
    }

    /** Set the event as a replay (not real-time). */
    public function replay(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_real_time' => false,
        ]);
    }

    /** Set the event as acknowledged. */
    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged_by' => User::factory(),
            'acknowledged_at' => now(),
        ]);
    }

    /** Set the event as dismissed. */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'dismissed_at' => now(),
        ]);
    }

    /** Set a face image path. */
    public function withFaceImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'face_image_path' => 'recognition/faces/'.fake()->uuid().'.jpg',
        ]);
    }

    /** Set a scene image path. */
    public function withSceneImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'scene_image_path' => 'recognition/scenes/'.fake()->uuid().'.jpg',
        ]);
    }
}
