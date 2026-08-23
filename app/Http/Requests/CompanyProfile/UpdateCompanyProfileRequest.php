<?php

namespace App\Http\Requests\CompanyProfile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'quotation_prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-\/]+$/'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],

            // Kept modest: this is a logo printed at document-header size,
            // not a photo asset.
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quotation_prefix.regex' => 'The quotation prefix may only contain letters, numbers, hyphens and slashes.',
            'phone.regex' => 'The phone may contain only digits, spaces and + - ( ).',
        ];
    }
}
