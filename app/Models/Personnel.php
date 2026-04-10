<?php

namespace App\Models;

use Database\Factories\PersonnelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable(['custom_id', 'name', 'person_type', 'gender', 'birthday', 'id_card', 'phone', 'address', 'photo_path', 'photo_hash'])]
class Personnel extends Model
{
    /** @use HasFactory<PersonnelFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'personnel';

    /** @var list<string> */
    protected $appends = ['photo_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'person_type' => 'integer',
            'gender' => 'integer',
            'birthday' => 'date',
        ];
    }

    /** Get the public URL for the personnel photo. */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->photo_path
            ? Storage::disk('public')->url($this->photo_path)
            : null
        );
    }
}
