<?php

namespace App\Jobs;

use App\Models\Camera;
use App\Services\CameraEnrollmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class EnrollPersonnelBatch implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  array<int>  $personnelIds
     */
    public function __construct(
        public Camera $camera,
        public array $personnelIds,
    ) {}

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('enrollment-camera-'.$this->camera->id))
                ->releaseAfter(30)
                ->expireAfter(300),
        ];
    }

    /** Execute the job. */
    public function handle(CameraEnrollmentService $service): void
    {
        $service->upsertBatch($this->camera, $this->personnelIds);
    }
}
