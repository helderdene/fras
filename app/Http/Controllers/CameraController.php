<?php

namespace App\Http\Controllers;

use App\Http\Requests\Camera\StoreCameraRequest;
use App\Http\Requests\Camera\UpdateCameraRequest;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Services\CameraEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CameraController extends Controller
{
    /** Display a listing of cameras. */
    public function index(): Response
    {
        return Inertia::render('cameras/Index', [
            'cameras' => Camera::orderBy('name')->get([
                'id', 'device_id', 'name', 'location_label', 'is_online', 'last_seen_at',
            ]),
        ]);
    }

    /** Show the form for creating a new camera. */
    public function create(): Response
    {
        return Inertia::render('cameras/Create', [
            'mapboxToken' => config('hds.mapbox.token'),
            'mapboxDarkStyle' => config('hds.mapbox.dark_style'),
            'mapboxLightStyle' => config('hds.mapbox.light_style'),
        ]);
    }

    /** Store a newly created camera. */
    public function store(StoreCameraRequest $request): RedirectResponse
    {
        $camera = Camera::create($request->validated());

        app(CameraEnrollmentService::class)->enrollAllToCamera($camera);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camera added.')]);

        return to_route('cameras.index');
    }

    /** Display the specified camera. */
    public function show(Camera $camera): Response
    {
        $enrolledPersonnel = CameraEnrollment::where('camera_id', $camera->id)
            ->with('personnel:id,name,custom_id,photo_path')
            ->orderBy('status')
            ->get()
            ->map(fn (CameraEnrollment $e) => [
                'id' => $e->personnel->id,
                'name' => $e->personnel->name,
                'photo_url' => $e->personnel->photo_url,
                'custom_id' => $e->personnel->custom_id,
                'enrollment_status' => $e->status,
                'enrolled_at' => $e->enrolled_at?->toIso8601String(),
            ]);

        return Inertia::render('cameras/Show', [
            'camera' => $camera,
            'enrolledPersonnel' => $enrolledPersonnel,
            'mapboxToken' => config('hds.mapbox.token'),
            'mapboxDarkStyle' => config('hds.mapbox.dark_style'),
            'mapboxLightStyle' => config('hds.mapbox.light_style'),
        ]);
    }

    /** Show the form for editing the specified camera. */
    public function edit(Camera $camera): Response
    {
        return Inertia::render('cameras/Edit', [
            'camera' => $camera,
            'mapboxToken' => config('hds.mapbox.token'),
            'mapboxDarkStyle' => config('hds.mapbox.dark_style'),
            'mapboxLightStyle' => config('hds.mapbox.light_style'),
        ]);
    }

    /** Update the specified camera. */
    public function update(UpdateCameraRequest $request, Camera $camera): RedirectResponse
    {
        $camera->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camera updated.')]);

        return to_route('cameras.show', $camera);
    }

    /** Remove the specified camera. */
    public function destroy(Camera $camera): RedirectResponse
    {
        $camera->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Camera deleted.')]);

        return to_route('cameras.index');
    }
}
