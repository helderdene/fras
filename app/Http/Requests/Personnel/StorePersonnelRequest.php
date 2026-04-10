<?php

namespace App\Http\Requests\Personnel;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePersonnelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is intentionally open to all authenticated users.
     * This is a single-site, single-operator facility; role-based
     * access control should be added here if multi-role support is
     * introduced in the future.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'custom_id' => ['required', 'string', 'max:48', 'unique:personnel,custom_id'],
            'name' => ['required', 'string', 'max:32'],
            'person_type' => ['required', 'integer', 'in:0,1'],
            'photo' => ['required', 'image', 'mimes:jpeg,png', 'max:10240'],
            'gender' => ['nullable', 'integer', 'in:0,1'],
            'birthday' => ['nullable', 'date'],
            'id_card' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:72'],
        ];
    }
}
