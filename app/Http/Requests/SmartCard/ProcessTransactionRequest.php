<?php

namespace App\Http\Requests\SmartCard;

use Illuminate\Foundation\Http\FormRequest;

class ProcessTransactionRequest extends FormRequest
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
            'card_number' => 'required|digits:16',
        'validity_date' => 'required',
        'cvv' => 'required|digits:3',
        'amount' => 'required|numeric|min:0.01|gt:40',
        ];
    }
}
