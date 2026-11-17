<?php

namespace App\Http\Requests\Roles;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreRoleRequest extends FormRequest
{
    /**
     * Derive a stable slug from the role name.
     *
     * The slug is the app's stable identity for a role, so it is never typed
     * by hand; only the name is.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name) && trim($name) !== '') {
            $this->merge([
                'slug' => Str::slug($name, '_'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'A role with a similar name already exists. Choose a different name.',
            'permissions.*.exists' => 'One of the selected permissions is invalid.',
        ];
    }
}
