<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Manuel Abonelik Oluşturma Request
 */
class CreateManualSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id'
            ],
            'subscription_plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id'
            ],
            'started_at' => [
                'nullable',
                'date',
                'before_or_equal:today'
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:started_at'
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
            'user_id.required' => 'Kullanıcı seçimi zorunludur.',
            'user_id.exists' => 'Seçilen kullanıcı bulunamadı.',
            'subscription_plan_id.required' => 'Abonelik planı seçimi zorunludur.',
            'subscription_plan_id.exists' => 'Seçilen plan bulunamadı.',
            'started_at.before_or_equal' => 'Başlangıç tarihi bugünden ileri olamaz.',
            'expires_at.after' => 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.',
            'admin_note.max' => 'Not en fazla 500 karakter olabilir.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'created_by' => $this->user()->id
        ]);
    }
}
