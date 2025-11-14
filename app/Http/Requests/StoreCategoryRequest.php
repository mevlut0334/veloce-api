<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Kullanıcının bu isteği yapmaya yetkisi var mı?
     */
    public function authorize(): bool
    {
        return true; // Yetkilendirmeyi controller'da yap
    }

    /**
     * Validasyon kuralları
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:150', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'show_on_home' => ['boolean'],
        ];
    }

    /**
     * Özel hata mesajları
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Kategori adı zorunludur.',
            'name.max' => 'Kategori adı en fazla 100 karakter olabilir.',
            'slug.max' => 'Slug en fazla 150 karakter olabilir.',
            'slug.unique' => 'Bu slug zaten kullanılıyor.',
            'description.max' => 'Açıklama en fazla 500 karakter olabilir.',
            'icon.max' => 'İkon metni en fazla 100 karakter olabilir.',
            'order.integer' => 'Sıra numarası tam sayı olmalıdır.',
            'order.min' => 'Sıra numarası 0\'dan küçük olamaz.',
        ];
    }

    /**
     * Attribute isimleri
     */
    public function attributes(): array
    {
        return [
            'name' => 'kategori adı',
            'slug' => 'slug',
            'description' => 'açıklama',
            'icon' => 'ikon',
            'order' => 'sıra',
            'is_active' => 'aktif durum',
            'show_on_home' => 'ana sayfada göster',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Checkbox değerlerini boolean'a çevir
        $merge = [];

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if ($this->has('show_on_home')) {
            $merge['show_on_home'] = $this->boolean('show_on_home');
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
