<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Abonelik Güncelleme Request
 */
class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => [
                'sometimes',
                'integer',
                'exists:subscription_plans,id'
            ],
            'started_at' => [
                'sometimes',
                'date'
            ],
            'expires_at' => [
                'sometimes',
                'date',
                'after:started_at'
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'expired', 'cancelled'])
            ],
            'admin_note' => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_plan_id.exists' => 'Seçilen plan bulunamadı.',
            'expires_at.after' => 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.',
            'status.in' => 'Geçersiz durum değeri.',
            'admin_note.max' => 'Not en fazla 500 karakter olabilir.',
        ];
    }
}
