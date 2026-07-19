<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handled in controller using policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['required', 'string', 'in:web,referral,cold_call,event,other'],
            'status' => ['nullable', 'string', 'in:new,contacted,qualified,won,lost'],
            'expected_value' => ['required', 'numeric', 'min:0'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
