<?php

namespace App\Mqtt\Handlers;

use App\Events\EnrollmentStatusChanged;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Mqtt\Contracts\MqttHandler;
use App\Services\CameraEnrollmentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AckHandler implements MqttHandler
{
    /** Handle an enrollment ACK response from a camera. */
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (! $data) {
            Log::warning('Invalid ACK payload', ['topic' => $topic]);

            return;
        }

        $messageId = $data['messageId'] ?? null;

        if (! $messageId) {
            Log::warning('ACK missing messageId', ['topic' => $topic]);

            return;
        }

        // Extract camera device_id from topic: mqtt/face/{device_id}/Ack
        $segments = explode('/', $topic);
        $deviceId = $segments[2] ?? null;

        if (! $deviceId) {
            Log::warning('ACK topic missing device_id', ['topic' => $topic]);

            return;
        }

        $camera = Camera::where('device_id', $deviceId)->first();

        if (! $camera) {
            Log::warning('ACK for unknown camera', ['device_id' => $deviceId]);

            return;
        }

        $cacheKey = "enrollment-ack:{$camera->id}:{$messageId}";
        $pending = Cache::pull($cacheKey);

        if (! $pending) {
            Log::warning('ACK for unknown/expired messageId', [
                'camera_id' => $camera->id,
                'messageId' => $messageId,
            ]);

            return;
        }

        $info = $data['info'] ?? [];

        Log::info('Processing enrollment ACK', [
            'camera_id' => $camera->id,
            'messageId' => $messageId,
            'successes' => count($info['AddSucInfo'] ?? []),
            'failures' => count($info['AddErrInfo'] ?? []),
        ]);

        $this->processSuccesses($camera->id, $info['AddSucInfo'] ?? [], $pending);
        $this->processFailures($camera->id, $info['AddErrInfo'] ?? []);
    }

    /**
     * Process successful enrollments from ACK response.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $pending
     */
    private function processSuccesses(int $cameraId, array $items, array $pending): void
    {
        foreach ($items as $item) {
            $customId = $item['customId'] ?? null;
            if (! $customId) {
                continue;
            }

            $enrollment = CameraEnrollment::where('camera_id', $cameraId)
                ->whereHas('personnel', fn ($q) => $q->where('custom_id', $customId))
                ->first();

            if (! $enrollment) {
                continue;
            }

            $enrollment->update([
                'status' => CameraEnrollment::STATUS_ENROLLED,
                'enrolled_at' => now(),
                'photo_hash' => $pending['photo_hashes'][$customId] ?? null,
                'last_error' => null,
            ]);

            EnrollmentStatusChanged::dispatch(
                $enrollment->personnel_id,
                $cameraId,
                CameraEnrollment::STATUS_ENROLLED,
                $enrollment->enrolled_at->toIso8601String(),
                null,
            );
        }
    }

    /**
     * Process failed enrollments from ACK response.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function processFailures(int $cameraId, array $items): void
    {
        foreach ($items as $item) {
            $customId = $item['customId'] ?? null;
            if (! $customId) {
                continue;
            }

            $errorMessage = app(CameraEnrollmentService::class)
                ->translateErrorCode((int) ($item['errcode'] ?? 0));

            $enrollment = CameraEnrollment::where('camera_id', $cameraId)
                ->whereHas('personnel', fn ($q) => $q->where('custom_id', $customId))
                ->first();

            if (! $enrollment) {
                continue;
            }

            $enrollment->update([
                'status' => CameraEnrollment::STATUS_FAILED,
                'last_error' => $errorMessage,
            ]);

            EnrollmentStatusChanged::dispatch(
                $enrollment->personnel_id,
                $cameraId,
                CameraEnrollment::STATUS_FAILED,
                null,
                $errorMessage,
            );
        }
    }
}
