<?php

namespace App\Http\Controllers;

use App\Enums\AlertSeverity;
use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /** Display the command center dashboard. */
    public function index(): Response
    {
        $cameras = Camera::select('id', 'device_id', 'name', 'location_label', 'latitude', 'longitude', 'is_online', 'last_seen_at')
            ->withCount(['recognitionEvents as today_recognition_count' => function ($query) {
                $query->whereDate('captured_at', today());
            }])
            ->get();

        $todayStats = [
            'recognitions' => RecognitionEvent::whereDate('captured_at', today())->where('is_real_time', true)->count(),
            'critical' => RecognitionEvent::whereDate('captured_at', today())->where('severity', AlertSeverity::Critical)->where('is_real_time', true)->count(),
            'warnings' => RecognitionEvent::whereDate('captured_at', today())->where('severity', AlertSeverity::Warning)->where('is_real_time', true)->count(),
            'enrolled' => Personnel::count(),
        ];

        $recentEvents = RecognitionEvent::with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path'])
            ->whereIn('severity', [AlertSeverity::Critical, AlertSeverity::Warning, AlertSeverity::Info])
            ->where('is_real_time', true)
            ->latest('captured_at')
            ->limit(50)
            ->get();

        return Inertia::render('Dashboard', [
            'cameras' => $cameras,
            'todayStats' => $todayStats,
            'recentEvents' => $recentEvents,
            'mapbox' => [
                'token' => config('hds.mapbox.token'),
                'darkStyle' => config('hds.mapbox.dark_style'),
                'lightStyle' => config('hds.mapbox.light_style'),
            ],
        ]);
    }

    /** Return the current queue depth as JSON. */
    public function queueDepth(): JsonResponse
    {
        return response()->json([
            'depth' => DB::table('jobs')->count(),
        ]);
    }
}
