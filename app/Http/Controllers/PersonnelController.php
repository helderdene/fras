<?php

namespace App\Http\Controllers;

use App\Http\Requests\Personnel\StorePersonnelRequest;
use App\Http\Requests\Personnel\UpdatePersonnelRequest;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use App\Services\CameraEnrollmentService;
use App\Services\PhotoProcessor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PersonnelController extends Controller
{
    /** Display a listing of personnel. */
    public function index(): Response
    {
        $personnel = Personnel::orderBy('name')->get();
        $totalPersonnel = $personnel->count();

        // Compute per-personnel sync status (worst across all cameras)
        $personnelWithSync = $personnel->map(function (Personnel $p) {
            $enrollments = CameraEnrollment::where('personnel_id', $p->id)->get();

            if ($enrollments->isEmpty()) {
                $syncStatus = 'not-synced';
            } elseif ($enrollments->contains('status', CameraEnrollment::STATUS_FAILED)) {
                $syncStatus = 'failed';
            } elseif ($enrollments->contains('status', CameraEnrollment::STATUS_PENDING)) {
                $syncStatus = 'pending';
            } elseif ($enrollments->every(fn ($e) => $e->status === CameraEnrollment::STATUS_ENROLLED)) {
                $syncStatus = 'synced';
            } else {
                $syncStatus = 'not-synced';
            }

            return array_merge($p->toArray(), ['sync_status' => $syncStatus]);
        });

        // Per-camera enrollment summary (D-10)
        $cameraSummary = Camera::select('cameras.id', 'cameras.name', 'cameras.is_online')
            ->withCount([
                'enrollments as enrolled_count' => fn ($q) => $q->where('status', CameraEnrollment::STATUS_ENROLLED),
                'enrollments as pending_count' => fn ($q) => $q->where('status', CameraEnrollment::STATUS_PENDING),
                'enrollments as failed_count' => fn ($q) => $q->where('status', CameraEnrollment::STATUS_FAILED),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($camera) => [
                'id' => $camera->id,
                'name' => $camera->name,
                'is_online' => $camera->is_online,
                'enrolled_count' => $camera->enrolled_count,
                'pending_count' => $camera->pending_count,
                'failed_count' => $camera->failed_count,
                'total_count' => $totalPersonnel,
            ]);

        return Inertia::render('personnel/Index', [
            'personnel' => $personnelWithSync,
            'cameraSummary' => $cameraSummary,
        ]);
    }

    /** Show the form for creating new personnel. */
    public function create(): Response
    {
        return Inertia::render('personnel/Create');
    }

    /** Store newly created personnel. */
    public function store(StorePersonnelRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $result = app(PhotoProcessor::class)->process($request->file('photo'));
            $data = array_merge($data, $result);
        }

        unset($data['photo']);

        $personnel = Personnel::create($data);

        app(CameraEnrollmentService::class)->enrollPersonnel($personnel);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personnel added.')]);

        return to_route('personnel.index');
    }

    /** Display the specified personnel. */
    public function show(Personnel $personnel): Response
    {
        $cameras = Camera::orderBy('name')
            ->get(['id', 'name', 'is_online'])
            ->map(function (Camera $camera) use ($personnel) {
                $enrollment = CameraEnrollment::where('camera_id', $camera->id)
                    ->where('personnel_id', $personnel->id)
                    ->first();

                return [
                    'id' => $camera->id,
                    'name' => $camera->name,
                    'is_online' => $camera->is_online,
                    'enrollment' => $enrollment ? [
                        'status' => $enrollment->status,
                        'enrolled_at' => $enrollment->enrolled_at?->toIso8601String(),
                        'last_error' => $enrollment->last_error,
                    ] : null,
                ];
            });

        return Inertia::render('personnel/Show', [
            'personnel' => $personnel,
            'cameras' => $cameras,
        ]);
    }

    /** Show the form for editing the specified personnel. */
    public function edit(Personnel $personnel): Response
    {
        return Inertia::render('personnel/Edit', [
            'personnel' => $personnel,
        ]);
    }

    /** Update the specified personnel. */
    public function update(UpdatePersonnelRequest $request, Personnel $personnel): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $oldPath = $personnel->photo_path;
            $result = app(PhotoProcessor::class)->process($request->file('photo'));
            $data = array_merge($data, $result);

            // Only delete old file after new file is confirmed stored
            app(PhotoProcessor::class)->delete($oldPath);
        }

        unset($data['photo']);

        $personnel->update($data);

        app(CameraEnrollmentService::class)->enrollPersonnel($personnel);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personnel updated.')]);

        return to_route('personnel.show', $personnel);
    }

    /** Remove the specified personnel. */
    public function destroy(Personnel $personnel): RedirectResponse
    {
        app(CameraEnrollmentService::class)->deleteFromAllCameras($personnel);

        app(PhotoProcessor::class)->delete($personnel->photo_path);

        $personnel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Personnel deleted and removed from cameras.')]);

        return to_route('personnel.index');
    }
}
