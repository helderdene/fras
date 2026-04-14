export type AlertSeverity = 'critical' | 'warning' | 'info';

export interface RecognitionEvent {
    id: number;
    camera_id: number;
    personnel_id: number | null;
    custom_id: string | null;
    camera_person_id: string | null;
    record_id: number;
    verify_status: number;
    person_type: number;
    similarity: number;
    is_real_time: boolean;
    name_from_camera: string | null;
    severity: AlertSeverity;
    face_image_url: string | null;
    scene_image_url: string | null;
    target_bbox: number[] | null;
    captured_at: string;
    acknowledged_by: number | null;
    acknowledged_at: string | null;
    acknowledger_name: string | null;
    dismissed_at: string | null;
    created_at: string;
    updated_at: string;
    camera?: { id: number; name: string };
    personnel?: {
        id: number;
        name: string;
        custom_id: string;
        person_type: number;
        photo_url: string | null;
    } | null;
}

export interface RecognitionAlertPayload {
    id: number;
    camera_id: number;
    camera_name: string;
    personnel_id: number | null;
    person_name: string | null;
    custom_id: string | null;
    severity: AlertSeverity;
    similarity: number;
    person_type: number;
    face_image_url: string | null;
    scene_image_url: string | null;
    target_bbox: number[] | null;
    captured_at: string;
    created_at: string;
}
