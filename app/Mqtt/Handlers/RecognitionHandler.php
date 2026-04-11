<?php

namespace App\Mqtt\Handlers;

use App\Enums\AlertSeverity;
use App\Events\RecognitionAlert;
use App\Models\Camera;
use App\Models\Personnel;
use App\Models\RecognitionEvent;
use App\Mqtt\Contracts\MqttHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RecognitionHandler implements MqttHandler
{
    /** Handle a RecPush recognition event from a camera. */
    public function handle(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (! $data || ($data['operator'] ?? null) !== 'RecPush') {
            return;
        }

        // Extract device_id from topic: mqtt/face/{device_id}/Rec
        $segments = explode('/', $topic);
        $deviceId = $segments[2] ?? null;

        if (! $deviceId) {
            Log::warning('RecPush topic missing device_id', ['topic' => $topic]);

            return;
        }

        $camera = Camera::where('device_id', $deviceId)->first();

        if (! $camera) {
            Log::warning('RecPush for unknown camera', ['device_id' => $deviceId]);

            return;
        }

        $info = $data['info'] ?? [];
        $parsed = $this->parsePayload($info);
        $severity = AlertSeverity::fromEvent($parsed['person_type'], $parsed['verify_status']);

        // Lookup personnel by custom_id
        $personnelId = null;
        if ($parsed['custom_id']) {
            $personnel = Personnel::where('custom_id', $parsed['custom_id'])->first();
            $personnelId = $personnel?->id;
        }

        // Insert event first (need ID for image filenames)
        $event = RecognitionEvent::create([
            'camera_id' => $camera->id,
            'personnel_id' => $personnelId,
            'custom_id' => $parsed['custom_id'],
            'camera_person_id' => $parsed['camera_person_id'],
            'record_id' => $parsed['record_id'],
            'verify_status' => $parsed['verify_status'],
            'person_type' => $parsed['person_type'],
            'similarity' => $parsed['similarity'],
            'is_real_time' => $parsed['is_real_time'],
            'name_from_camera' => $parsed['name_from_camera'],
            'facesluice_id' => $parsed['facesluice_id'],
            'id_card' => $parsed['id_card'],
            'phone' => $parsed['phone'],
            'is_no_mask' => $parsed['is_no_mask'],
            'target_bbox' => $parsed['target_bbox'],
            'captured_at' => $parsed['captured_at'],
            'severity' => $severity,
            'raw_payload' => $data,
        ]);

        // Save images using event ID as filename
        $date = $event->captured_at->format('Y-m-d');
        $faceImagePath = $this->saveImage($info['pic'] ?? null, 'face', $event->id, $date, 1048576);
        $sceneImagePath = $this->saveImage($info['scene'] ?? null, 'scene', $event->id, $date, 2097152);

        if ($faceImagePath || $sceneImagePath) {
            $event->update(array_filter([
                'face_image_path' => $faceImagePath,
                'scene_image_path' => $sceneImagePath,
            ]));
        }

        // Dispatch broadcast event for real-time events with broadcastable severity.
        if ($parsed['is_real_time'] && $severity->shouldBroadcast()) {
            $event->load(['camera', 'personnel']);
            event(RecognitionAlert::fromEvent($event));
        }

        Log::info('RecPush processed', [
            'event_id' => $event->id,
            'camera_id' => $camera->id,
            'severity' => $severity->value,
            'is_real_time' => $parsed['is_real_time'],
        ]);
    }

    /**
     * Parse RecPush info payload with firmware quirk handling.
     *
     * @param  array<string, mixed>  $info
     * @return array<string, mixed>
     */
    private function parsePayload(array $info): array
    {
        return [
            'custom_id' => trim($info['customId'] ?? '') ?: null,
            'camera_person_id' => $info['personId'] ?? null,
            'record_id' => (int) ($info['RecordID'] ?? 0),
            'verify_status' => (int) ($info['VerifyStatus'] ?? 0),
            'person_type' => (int) ($info['PersonType'] ?? 0),
            'similarity' => (float) ($info['similarity1'] ?? 0),
            'is_real_time' => ($info['Sendintime'] ?? 0) === 1 && ($info['PushType'] ?? 0) !== 2,
            'name_from_camera' => $info['personName'] ?? $info['persionName'] ?? null,
            'facesluice_id' => $info['facesluiceId'] ?? null,
            'id_card' => trim($info['idCard'] ?? '') ?: null,
            'phone' => trim($info['telnum'] ?? '') ?: null,
            'is_no_mask' => (int) ($info['isNoMask'] ?? 0),
            'target_bbox' => $info['targetPosInScene'] ?? null,
            'captured_at' => $info['time'] ?? now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Decode base64 image, validate size, save to date-partitioned storage.
     *
     * Returns the storage path or null if the image is missing, invalid, or oversized.
     */
    private function saveImage(?string $dataUri, string $type, int $eventId, string $date, int $maxBytes): ?string
    {
        if (! $dataUri) {
            return null;
        }

        $base64 = preg_replace('#^data:image/\w+;base64,#', '', $dataUri);
        $imageData = base64_decode($base64, true);

        if ($imageData === false || strlen($imageData) === 0) {
            Log::warning("Failed to decode {$type} image", ['event_id' => $eventId]);

            return null;
        }

        if (strlen($imageData) > $maxBytes) {
            Log::warning("{$type} image exceeds size limit", [
                'event_id' => $eventId,
                'size' => strlen($imageData),
                'max' => $maxBytes,
            ]);

            return null;
        }

        $directory = "recognition/{$date}/{$type}s";
        $path = "{$directory}/{$eventId}.jpg";

        Storage::disk('local')->put($path, $imageData);

        return $path;
    }
}
