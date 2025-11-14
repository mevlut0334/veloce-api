<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderHomeSectionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sections' => [
                'required',
                'array',
                'min:1',
            ],
            'sections.*.id' => [
                'required',
                'integer',
                'exists:home_sections,id',
            ],
            'sections.*.order' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Custom validation
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sections = $this->input('sections', []);

            if (empty($sections)) {
                return;
            }

            // ID'lerin unique olup olmadığını kontrol et
            $ids = array_column($sections, 'id');
            if (count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('sections', 'Section ID\'leri tekrar edemez.');
            }

            // Order değerlerinin unique olup olmadığını kontrol et
            $orders = array_column($sections, 'order');
            if (count($orders) !== count(array_unique($orders))) {
                $validator->errors()->add('sections', 'Sıralama değerleri tekrar edemez.');
            }

            // Order değerlerinin ardışık olup olmadığını kontrol et (1,2,3,4...)
            sort($orders);
            $expectedOrders = range(1, count($orders));
            if ($orders !== $expectedOrders) {
                $validator->errors()->add('sections', 'Sıralama değerleri 1\'den başlayarak ardışık olmalıdır.');
            }
        });
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'sections.required' => 'Section listesi zorunludur.',
            'sections.array' => 'Section listesi dizi formatında olmalıdır.',
            'sections.min' => 'En az bir section gönderilmelidir.',
            'sections.*.id.required' => 'Section ID zorunludur.',
            'sections.*.id.integer' => 'Section ID sayı olmalıdır.',
            'sections.*.id.exists' => 'Section bulunamadı.',
            'sections.*.order.required' => 'Sıralama değeri zorunludur.',
            'sections.*.order.integer' => 'Sıralama değeri sayı olmalıdır.',
            'sections.*.order.min' => 'Sıralama değeri 0\'dan küçük olamaz.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // JSON string olarak gelebilir
        if ($this->has('sections') && is_string($this->sections)) {
            $this->merge([
                'sections' => json_decode($this->sections, true) ?? [],
            ]);
        }
    }
}
