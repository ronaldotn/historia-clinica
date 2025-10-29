<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePractitionerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Para route-model binding {practitioner}, Laravel inyecta el modelo.
        $practitionerId = $this->route('practitioner')?->id ?? $this->route('practitioner');

        return [
            'first_name'      => 'sometimes|required|string|max:100',
            'last_name'       => 'sometimes|required|string|max:100',
            'specialty'       => 'nullable|string|max:100',
            'identifier'      => ['sometimes','required','string','max:50', Rule::unique('practitioners','identifier')->ignore($practitionerId)],
            'email'           => ['sometimes','required','email','max:100', Rule::unique('practitioners','email')->ignore($practitionerId)],
            'phone'           => 'nullable|string|max:25',
            'organization_id' => 'nullable|integer|exists:organizations,id',
            'active'          => 'sometimes|boolean',
        ];
    }
}
