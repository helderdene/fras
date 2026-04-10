<?php

namespace App\Mqtt\Handlers;

use App\Events\CameraStatusChanged;
use App\Models\Camera;
use App\Mqtt\Contracts\MqttHandler;
use Illuminate\Support\Facades\Log;

class OnlineOfflineHandler implements MqttHandler
{
    /** Handle an Online/Offline status message from a camera. */
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (! $data || ! isset($data['operator'])) {
            Log::warning('Invalid Online/Offline payload', ['topic' => $topic]);

            return;
        }

        $operator = $data['operator'];

        if (! in_array($operator, ['Online', 'Offline'], true)) {
            Log::warning('Unexpected operator on basic topic', [
                'topic' => $topic,
                'operator' => $operator,
            ]);

            return;
        }

        $facesluiceId = $data['info']['facesluiceId'] ?? null;

        if (! $facesluiceId) {
            Log::warning('Online/Offline missing facesluiceId', ['topic' => $topic]);

            return;
        }

        $camera = Camera::where('device_id', $facesluiceId)->first();

        if (! $camera) {
            Log::warning('Online/Offline for unknown camera', [
                'facesluiceId' => $facesluiceId,
            ]);

            return;
        }

        $isOnline = $operator === 'Online';
        $wasOnline = $camera->is_online;

        $camera->is_online = $isOnline;

        if ($isOnline) {
            $camera->last_seen_at = now();
        }

        $camera->save();

        // Only broadcast if status actually changed (D-06, anti-pattern #4)
        if ($wasOnline !== $isOnline) {
            CameraStatusChanged::dispatch(
                $camera->id,
                $camera->name,
                $isOnline,
                $camera->last_seen_at?->toIso8601String(),
            );
        }
    }
}
