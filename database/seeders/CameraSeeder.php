<?php

namespace Database\Seeders;

use App\Models\Camera;
use Illuminate\Database\Seeder;

class CameraSeeder extends Seeder
{
    /** Seed the cameras table with Butuan City locations. */
    public function run(): void
    {
        Camera::factory()->create([
            'device_id' => '1026700',
            'name' => 'Main Entrance',
            'location_label' => 'Building A, Ground Floor',
            'latitude' => 8.9475785,
            'longitude' => 125.5406434,
        ]);

        Camera::factory()->create([
            'device_id' => '1026701',
            'name' => 'Back Gate',
            'location_label' => 'Building A, Rear',
            'latitude' => 8.9472100,
            'longitude' => 125.5410200,
        ]);

        Camera::factory()->create([
            'device_id' => '1026702',
            'name' => 'Lobby',
            'location_label' => 'Building B, Ground Floor',
            'latitude' => 8.9478500,
            'longitude' => 125.5403100,
        ]);

        Camera::factory()->create([
            'device_id' => '1026703',
            'name' => 'Parking Lot',
            'location_label' => 'Outdoor, East Side',
            'latitude' => 8.9470300,
            'longitude' => 125.5412800,
        ]);
    }
}
