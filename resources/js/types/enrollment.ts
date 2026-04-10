export interface CameraWithEnrollment {
    id: number;
    name: string;
    is_online: boolean;
    enrollment: {
        status: 'enrolled' | 'pending' | 'failed' | 'not-synced';
        enrolled_at: string | null;
        last_error: string | null;
    } | null;
}

export interface CameraEnrollmentSummary {
    id: number;
    name: string;
    is_online: boolean;
    enrolled_count: number;
    pending_count: number;
    failed_count: number;
    total_count: number;
}

export interface EnrollmentStatusPayload {
    personnel_id: number;
    camera_id: number;
    status: 'enrolled' | 'pending' | 'failed';
    enrolled_at: string | null;
    last_error: string | null;
}

export interface EnrolledPerson {
    id: number;
    name: string;
    photo_url: string | null;
    custom_id: string;
    enrollment_status: 'enrolled' | 'pending' | 'failed';
    enrolled_at: string | null;
}

export interface PersonnelWithSync {
    id: number;
    custom_id: string;
    name: string;
    person_type: number;
    photo_url: string | null;
    sync_status: 'synced' | 'pending' | 'failed' | 'not-synced';
}
