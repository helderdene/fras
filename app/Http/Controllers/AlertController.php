<?php

namespace App\Http\Controllers;

use App\Enums\AlertSeverity;
use App\Models\RecognitionEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlertController extends Controller
{
    /** Display the live alert feed page. */
    public function index(): Response
    {
        $events = RecognitionEvent::with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path'])
            ->whereIn('severity', [AlertSeverity::Critical, AlertSeverity::Warning, AlertSeverity::Info])
            ->where('is_real_time', true)
            ->latest('captured_at')
            ->limit(50)
            ->get();

        return Inertia::render('alerts/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Acknowledge an alert event.
     *
     * Authorization: All authenticated users may acknowledge any event.
     * This is intentional for a single command center with trusted operators.
     * If role-based access is needed later, add a RecognitionEventPolicy.
     */
    public function acknowledge(RecognitionEvent $event): RedirectResponse
    {
        $event->update([
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert acknowledged.')]);

        return back();
    }

    /**
     * Dismiss an alert event.
     *
     * Authorization: All authenticated users may dismiss any event.
     * Single command center with trusted operators -- see acknowledge() note.
     */
    public function dismiss(RecognitionEvent $event): RedirectResponse
    {
        $event->update([
            'dismissed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alert dismissed.')]);

        return back();
    }

    /**
     * Serve face crop image from local storage (auth-protected).
     *
     * Authorization: All authenticated users may access any event image.
     * Single command center with trusted operators -- see acknowledge() note.
     */
    public function faceImage(RecognitionEvent $event): StreamedResponse
    {
        if (! $event->face_image_path || ! Storage::disk('local')->exists($event->face_image_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($event->face_image_path);
    }

    /**
     * Serve scene image from local storage (auth-protected).
     *
     * Authorization: All authenticated users may access any event image.
     * Single command center with trusted operators -- see acknowledge() note.
     */
    public function sceneImage(RecognitionEvent $event): StreamedResponse
    {
        if (! $event->scene_image_path || ! Storage::disk('local')->exists($event->scene_image_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($event->scene_image_path);
    }
}
