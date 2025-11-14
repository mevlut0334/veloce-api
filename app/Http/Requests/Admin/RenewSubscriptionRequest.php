<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Abonelik Yenileme Request
 */
class RenewSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id'
            ],
            'note' => [
                'nullable',
                'string',
                'max:255'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Plan seçimi zorunludur.',
            'plan_id.exists' => 'Seçilen plan bulunamadı.',
            'note.max' => 'Not en fazla 255 karakter olabilir.',
        ];
    }
}
