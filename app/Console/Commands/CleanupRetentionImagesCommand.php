<?php

namespace App\Console\Commands;

use App\Models\RecognitionEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupRetentionImagesCommand extends Command
{
    /** The console command signature. */
    protected $signature = 'fras:cleanup-retention-images';

    /** The console command description. */
    protected $description = 'Delete expired face crop and scene images per retention policy';

    /** Execute the console command. */
    public function handle(): int
    {
        $sceneRetentionDays = config('hds.retention.scene_images_days', 30);
        $faceRetentionDays = config('hds.retention.face_crops_days', 90);

        $sceneCount = $this->cleanupImages('scene_image_path', $sceneRetentionDays);
        $faceCount = $this->cleanupImages('face_image_path', $faceRetentionDays);

        $message = "Retention cleanup: deleted {$sceneCount} scene images, {$faceCount} face crops";

        Log::info($message);
        $this->info($message);

        return self::SUCCESS;
    }

    /** Delete expired images for the given column and return the count of processed events. */
    private function cleanupImages(string $column, int $retentionDays): int
    {
        $cutoff = now()->subDays($retentionDays);
        $count = 0;

        RecognitionEvent::query()
            ->whereNotNull($column)
            ->where('captured_at', '<', $cutoff)
            ->chunkById(200, function ($events) use ($column, &$count) {
                foreach ($events as $event) {
                    $path = $event->{$column};

                    if ($path && Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }

                    $event->update([$column => null]);
                    $count++;
                }
            });

        return $count;
    }
}
