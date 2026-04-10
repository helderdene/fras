<?php

namespace App\Console\Commands;

use App\Events\CameraStatusChanged;
use App\Models\Camera;
use Illuminate\Console\Command;

class CheckOfflineCamerasCommand extends Command
{
    /** The console command signature. */
    protected $signature = 'fras:check-offline-cameras';

    /** The console command description. */
    protected $description = 'Mark cameras as offline when heartbeat is absent beyond the configured threshold';

    /** Execute the console command. */
    public function handle(): int
    {
        $threshold = config('hds.alerts.camera_offline_threshold', 90);

        $staleCameras = Camera::where('is_online', true)
            ->where('last_seen_at', '<', now()->subSeconds($threshold))
            ->get();

        foreach ($staleCameras as $camera) {
            $camera->is_online = false;
            $camera->save();

            CameraStatusChanged::dispatch(
                $camera->id,
                $camera->name,
                false,
                $camera->last_seen_at?->toIso8601String(),
            );

            $this->info("Camera [{$camera->name}] marked offline (last seen: {$camera->last_seen_at})");
        }

        if ($staleCameras->isEmpty()) {
            $this->info('No stale cameras found.');
        }

        return self::SUCCESS;
    }
}
