<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateLeadRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['sometimes', 'required', new Enum(LeadSource::class)],
            'status' => ['sometimes', 'required', new Enum(LeadStatus::class)],
            'expected_value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'rep'),
            ],
        ];
    }
}
