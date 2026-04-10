export interface Personnel {
    id: number;
    custom_id: string;
    name: string;
    person_type: number; // 0=allow, 1=block
    gender: number | null;
    birthday: string | null;
    id_card: string | null;
    phone: string | null;
    address: string | null;
    photo_path: string | null;
    photo_hash: string | null;
    photo_url: string | null;
    created_at: string;
    updated_at: string;
}
