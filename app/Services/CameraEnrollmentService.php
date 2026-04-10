<?php

namespace App\Services;

use App\Jobs\EnrollPersonnelBatch;
use App\Models\Camera;
use App\Models\CameraEnrollment;
use App\Models\Personnel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpMqtt\Client\Facades\MQTT;

class CameraEnrollmentService
{
    /**
     * Enroll a personnel record to all cameras (D-01, D-03).
     *
     * Creates pending enrollment rows for all cameras.
     * Dispatches jobs only for online cameras.
     */
    public function enrollPersonnel(Personnel $personnel): void
    {
        $cameras = Camera::all();

        foreach ($cameras as $camera) {
            CameraEnrollment::updateOrCreate(
                ['camera_id' => $camera->id, 'personnel_id' => $personnel->id],
                ['status' => CameraEnrollment::STATUS_PENDING, 'last_error' => null]
            );

            if ($camera->is_online) {
                EnrollPersonnelBatch::dispatch($camera, [$personnel->id]);
            }
        }
    }

    /**
     * Enroll all personnel to a single camera (D-02).
     *
     * Creates pending enrollment rows and dispatches chunked jobs.
     */
    public function enrollAllToCamera(Camera $camera): void
    {
        $personnelIds = Personnel::pluck('id')->toArray();

        foreach ($personnelIds as $personnelId) {
            CameraEnrollment::updateOrCreate(
                ['camera_id' => $camera->id, 'personnel_id' => $personnelId],
                ['status' => CameraEnrollment::STATUS_PENDING, 'last_error' => null]
            );
        }

        if ($camera->is_online) {
            $batchSize = config('hds.enrollment.batch_size');

            foreach (array_chunk($personnelIds, $batchSize) as $chunk) {
                EnrollPersonnelBatch::dispatch($camera, $chunk);
            }
        }
    }

    /**
     * Execute an enrollment batch: build payload, cache correlation, publish MQTT.
     *
     * @param  array<int>  $personnelIds
     */
    public function upsertBatch(Camera $camera, array $personnelIds): void
    {
        $personnel = Personnel::whereIn('id', $personnelIds)->get();
        $batchSize = config('hds.enrollment.batch_size');

        foreach ($personnel->chunk($batchSize) as $chunk) {
            $messageId = 'EditPersonsNew'.now()->format('Y-m-d\TH:i:s').'_'.Str::random(6);

            $payload = $this->buildEditPersonsNewPayload($camera, $chunk, $messageId);

            Cache::put(
                "enrollment-ack:{$camera->id}:{$messageId}",
                [
                    'camera_id' => $camera->id,
                    'personnel_ids' => $chunk->pluck('id')->toArray(),
                    'photo_hashes' => $chunk->pluck('photo_hash', 'custom_id')->toArray(),
                    'dispatched_at' => now()->toIso8601String(),
                ],
                config('hds.enrollment.ack_timeout_minutes') * 60
            );

            $prefix = config('hds.mqtt.topic_prefix');
            MQTT::publish("{$prefix}/{$camera->device_id}/Edit", json_encode($payload));
        }
    }

    /**
     * Build an EditPersonsNew MQTT payload per FRAS spec section 3.5.
     *
     * @return array<string, mixed>
     */
    public function buildEditPersonsNewPayload(Camera $camera, Collection $personnelRecords, string $messageId): array
    {
        $info = [];

        foreach ($personnelRecords as $personnel) {
            $entry = [
                'customId' => $personnel->custom_id,
                'name' => $personnel->name,
                'personType' => $personnel->person_type,
                'isCheckSimilarity' => 1,
            ];

            if ($personnel->photo_path) {
                $entry['picURI'] = $personnel->photo_url;
            }

            if ($personnel->gender !== null) {
                $entry['gender'] = $personnel->gender;
            }

            if ($personnel->birthday) {
                $entry['birthday'] = $personnel->birthday->format('Y-m-d');
            }

            if ($personnel->id_card) {
                $entry['idCard'] = $personnel->id_card;
            }

            if ($personnel->phone) {
                $entry['telnum1'] = $personnel->phone;
            }

            if ($personnel->address) {
                $entry['address'] = $personnel->address;
            }

            $info[] = $entry;
        }

        return [
            'messageId' => $messageId,
            'DataBegin' => 'BeginFlag',
            'operator' => 'EditPersonsNew',
            'PersonNum' => count($info),
            'info' => $info,
            'DataEnd' => 'EndFlag',
        ];
    }

    /**
     * Build a DeletePersons MQTT payload.
     *
     * @param  array<string>  $customIds
     * @return array<string, mixed>
     */
    public function buildDeletePersonsPayload(array $customIds, string $messageId): array
    {
        return [
            'messageId' => $messageId,
            'operator' => 'DeletePersons',
            'info' => array_map(fn (string $id) => ['customId' => $id], $customIds),
        ];
    }

    /**
     * Delete personnel from all enrolled cameras via MQTT (D-12).
     *
     * Fire-and-forget: no ACK tracking for deletes.
     */
    public function deleteFromAllCameras(Personnel $personnel): void
    {
        $enrollments = CameraEnrollment::where('personnel_id', $personnel->id)
            ->with('camera')
            ->get();

        $prefix = config('hds.mqtt.topic_prefix');

        foreach ($enrollments as $enrollment) {
            if ($enrollment->camera && $enrollment->camera->is_online) {
                $messageId = 'DeletePersons'.now()->format('Y-m-d\TH:i:s').'_'.Str::random(6);
                $payload = $this->buildDeletePersonsPayload([$personnel->custom_id], $messageId);

                MQTT::publish("{$prefix}/{$enrollment->camera->device_id}/Edit", json_encode($payload));
            }
        }
    }

    /**
     * Translate a camera enrollment error code to an operator-friendly message.
     *
     * Error codes per FRAS spec appendix.
     */
    public function translateErrorCode(int $code): string
    {
        return match ($code) {
            461 => 'Internal error: missing personnel ID',
            463 => 'Photo required for first enrollment',
            464 => 'Camera could not resolve photo host',
            465 => 'Camera could not download photo',
            466 => 'Photo URL returned no data',
            467 => 'Photo too large; re-upload with smaller file',
            468 => 'No usable face detected in photo',
            474 => 'Camera storage full; remove old enrollments',
            478 => 'Person may already be enrolled',
            default => 'Enrollment failed. Try again or check camera connectivity.',
        };
    }
}
