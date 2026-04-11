<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\RecognitionEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventHistoryController extends Controller
{
    /** Display the event history page with server-side filtered pagination. */
    public function index(Request $request): Response
    {
        $allowedSorts = ['captured_at', 'similarity', 'severity'];

        $sort = in_array($request->input('sort'), $allowedSorts, true)
            ? $request->input('sort')
            : 'captured_at';

        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $dateFrom = $request->input('date_from', now()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $events = RecognitionEvent::query()
            ->with(['camera:id,name', 'personnel:id,name,custom_id,person_type,photo_path'])
            ->whereDate('captured_at', '>=', $dateFrom)
            ->whereDate('captured_at', '<=', $dateTo)
            ->when($request->input('camera_id'), fn ($q, $id) => $q->where('camera_id', $id))
            ->when($request->input('severity'), fn ($q, $sev) => $q->where('severity', $sev))
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name_from_camera', 'like', "%{$search}%")
                        ->orWhere('recognition_events.custom_id', 'like', "%{$search}%")
                        ->orWhereHas('personnel', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('custom_id', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('events/Index', [
            'events' => $events,
            'cameras' => Camera::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'camera_id' => $request->input('camera_id'),
                'search' => $request->input('search'),
                'severity' => $request->input('severity'),
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }
}
