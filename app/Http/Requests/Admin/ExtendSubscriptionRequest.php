<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Abonelik Uzatma Request
 */
class ExtendSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'days' => [
                'required',
                'integer',
                'min:1',
                'max:730' // Maksimum 2 yıl
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
            'days.required' => 'Gün sayısı zorunludur.',
            'days.min' => 'En az 1 gün uzatabilirsiniz.',
            'days.max' => 'En fazla 730 gün (2 yıl) uzatabilirsiniz.',
            'note.max' => 'Not en fazla 255 karakter olabilir.',
        ];
    }
}
