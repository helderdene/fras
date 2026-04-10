<?php

use Illuminate\Support\Facades\Schema;

test('cameras table has expected columns', function () {
    expect(Schema::hasTable('cameras'))->toBeTrue();
    expect(Schema::hasColumns('cameras', [
        'id', 'device_id', 'name', 'location_label',
        'latitude', 'longitude', 'last_seen_at', 'is_online',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('personnel table has expected columns', function () {
    expect(Schema::hasTable('personnel'))->toBeTrue();
    expect(Schema::hasColumns('personnel', [
        'id', 'custom_id', 'name', 'person_type', 'gender',
        'birthday', 'id_card', 'phone', 'address',
        'photo_path', 'photo_hash', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('recognition_events table has expected columns', function () {
    expect(Schema::hasTable('recognition_events'))->toBeTrue();
    expect(Schema::hasColumns('recognition_events', [
        'id', 'camera_id', 'personnel_id', 'custom_id',
        'camera_person_id', 'record_id', 'verify_status',
        'person_type', 'similarity', 'is_real_time',
        'name_from_camera', 'facesluice_id', 'id_card', 'phone',
        'is_no_mask', 'target_bbox', 'captured_at',
        'face_image_path', 'scene_image_path', 'raw_payload',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('camera_enrollments table has expected columns', function () {
    expect(Schema::hasTable('camera_enrollments'))->toBeTrue();
    expect(Schema::hasColumns('camera_enrollments', [
        'id', 'camera_id', 'personnel_id', 'enrolled_at',
        'photo_hash', 'last_error', 'created_at', 'updated_at',
    ]))->toBeTrue();
});
