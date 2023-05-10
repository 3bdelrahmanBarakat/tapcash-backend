<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class AddEmployeesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return True;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'employees.*company_id' => 'required|string',
            'employees.*phone_number' => 'required|string|exists:users,phone_number|min:11|max:11',
            'employees.*position' => 'required|string|min:1',
            'employees.*salary' => 'required|string|gt:30',
        ];
    }
}
