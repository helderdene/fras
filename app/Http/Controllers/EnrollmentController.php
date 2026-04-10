<?php

namespace App\Http\Controllers;

use App\Http\Requests\Enrollment\ResyncAllRequest;
use App\Http\Requests\Enrollment\RetryEnrollmentRequest;
use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class EnrollmentController extends Controller
{
    /** Retry failed enrollment for a single camera. Per D-07. */
    public function retry(RetryEnrollmentRequest $request, Personnel $personnel, Camera $camera): RedirectResponse
    {
        CameraEnrollment::updateOrCreate(
            ['camera_id' => $camera->id, 'personnel_id' => $personnel->id],
            ['status' => CameraEnrollment::STATUS_PENDING, 'last_error' => null]
        );

        if ($camera->is_online) {
            EnrollPersonnelBatch::dispatch($camera, [$personnel->id]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Retrying enrollment to :camera.', ['camera' => $camera->name]),
        ]);

        return back();
    }

    /** Re-sync all cameras for a personnel. Per D-08. */
    public function resyncAll(ResyncAllRequest $request, Personnel $personnel): RedirectResponse
    {
        // Reset all existing enrollment statuses to pending in one query
        CameraEnrollment::where('personnel_id', $personnel->id)
            ->update(['status' => CameraEnrollment::STATUS_PENDING, 'last_error' => null]);

        // Create missing rows for cameras not yet enrolled (insert-only)
        $existingCameraIds = CameraEnrollment::where('personnel_id', $personnel->id)
            ->pluck('camera_id');

        $allCameras = Camera::all();

        foreach ($allCameras as $camera) {
            if (! $existingCameraIds->contains($camera->id)) {
                CameraEnrollment::create([
                    'camera_id' => $camera->id,
                    'personnel_id' => $personnel->id,
                    'status' => CameraEnrollment::STATUS_PENDING,
                ]);
            }

            if ($camera->is_online) {
                EnrollPersonnelBatch::dispatch($camera, [$personnel->id]);
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Re-syncing to all cameras.'),
        ]);

        return back();
    }
}
