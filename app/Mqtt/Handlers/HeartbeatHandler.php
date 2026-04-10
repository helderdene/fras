<?php

namespace App\Mqtt\Handlers;

use App\Models\Camera;
use App\Mqtt\Contracts\MqttHandler;
use Illuminate\Support\Facades\Log;

class HeartbeatHandler implements MqttHandler
{
    /** Handle a HeartBeat message from a camera. */
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (($data['operator'] ?? '') !== 'HeartBeat') {
            Log::warning('Unexpected operator on heartbeat topic', ['topic' => $topic]);

            return;
        }

        $facesluiceId = $data['info']['facesluiceId'] ?? null;

        if (! $facesluiceId) {
            Log::warning('Heartbeat missing facesluiceId', ['topic' => $topic]);

            return;
        }

        $updated = Camera::where('device_id', $facesluiceId)
            ->update(['last_seen_at' => now()]);

        if ($updated === 0) {
            Log::warning('Heartbeat for unknown camera', [
                'facesluiceId' => $facesluiceId,
                'topic' => $topic,
            ]);
        }
    }
}
