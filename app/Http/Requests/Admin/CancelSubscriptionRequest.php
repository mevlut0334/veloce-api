<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Abonelik İptal Request
 */
class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:500'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'İptal nedeni zorunludur.',
            'reason.min' => 'İptal nedeni en az 10 karakter olmalıdır.',
            'reason.max' => 'İptal nedeni en fazla 500 karakter olabilir.',
        ];
    }
}
