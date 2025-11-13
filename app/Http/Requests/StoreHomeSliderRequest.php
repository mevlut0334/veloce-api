<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeSliderRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_link' => ['nullable', 'url', 'max:500'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB
            'video_id' => ['nullable', 'exists:videos,id'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ];
    }

    /**
     * Özel hata mesajları
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Slider başlığı zorunludur.',
            'title.max' => 'Slider başlığı en fazla 255 karakter olabilir.',
            'subtitle.max' => 'Alt başlık en fazla 500 karakter olabilir.',
            'button_text.max' => 'Buton metni en fazla 100 karakter olabilir.',
            'button_link.url' => 'Buton linki geçerli bir URL olmalıdır.',
            'image.required' => 'Slider görseli zorunludur.',
            'image.image' => 'Yüklenen dosya bir görsel olmalıdır.',
            'image.mimes' => 'Görsel formatı jpeg, jpg, png veya webp olmalıdır.',
            'image.max' => 'Görsel boyutu en fazla 5MB olabilir.',
            'video_id.exists' => 'Seçilen video bulunamadı.',
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
            'title' => 'başlık',
            'subtitle' => 'alt başlık',
            'button_text' => 'buton metni',
            'button_link' => 'buton linki',
            'image' => 'görsel',
            'video_id' => 'video',
            'is_active' => 'aktif durum',
            'order' => 'sıra',
        ];
    }
}
