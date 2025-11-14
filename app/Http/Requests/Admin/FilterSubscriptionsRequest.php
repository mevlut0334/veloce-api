<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Abonelik Filtreleme Request
 */
class FilterSubscriptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],
            'status' => [
                'nullable',
                Rule::in(['active', 'expired', 'cancelled'])
            ],
            'subscription_type' => [
                'nullable',
                Rule::in(['manual', 'paid'])
            ],
            'plan_id' => [
                'nullable',
                'integer',
                'exists:subscription_plans,id'
            ],
            'start_date' => [
                'nullable',
                'date'
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date'
            ],
            'search' => [
                'nullable',
                'string',
                'max:255'
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:10',
                'max:100'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'Seçilen kullanıcı bulunamadı.',
            'status.in' => 'Geçersiz durum değeri.',
            'subscription_type.in' => 'Geçersiz abonelik tipi.',
            'plan_id.exists' => 'Seçilen plan bulunamadı.',
            'end_date.after_or_equal' => 'Bitiş tarihi başlangıç tarihinden önce olamaz.',
            'search.max' => 'Arama terimi en fazla 255 karakter olabilir.',
            'per_page.min' => 'Sayfa başına en az 10 kayıt gösterilebilir.',
            'per_page.max' => 'Sayfa başına en fazla 100 kayıt gösterilebilir.',
        ];
    }
}
