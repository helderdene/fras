<?php

namespace App\Http\Controllers;

use App\Http\Requests\Camera\StoreCameraRequest;
use App\Http\Requests\Camera\UpdateCameraRequest;
use App\Models\Camera;
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
        return Inertia::render('cameras/Show', [
            'camera' => $camera,
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
