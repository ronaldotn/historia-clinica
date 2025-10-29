<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePractitionerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorización por middleware (auth, scopes, roles)
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'specialty'      => 'nullable|string|max:100',
            'identifier'     => 'required|string|max:50|unique:practitioners,identifier',
            'email'          => 'required|email|max:100|unique:practitioners,email',
            'phone'          => 'nullable|string|max:25',
            'organization_id'=> 'nullable|integer|exists:organizations,id',
            'active'         => 'sometimes|boolean',
        ];
    }
}
