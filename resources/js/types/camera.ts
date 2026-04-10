export interface Camera {
    id: number;
    device_id: string;
    name: string;
    location_label: string;
    latitude: number;
    longitude: number;
    is_online: boolean;
    last_seen_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface CameraStatusPayload {
    camera_id: number;
    camera_name: string;
    is_online: boolean;
    last_seen_at: string | null;
}
