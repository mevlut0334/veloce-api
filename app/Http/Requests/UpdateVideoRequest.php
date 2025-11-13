<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin kontrolü middleware'de yapılıyor
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Video bilgileri
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',

            // Dosyalar (güncelleme sırasında opsiyonel)
            'video' => 'nullable|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo|max:512000', // 500MB
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB

            // Video özellikleri
            'is_premium' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'orientation' => 'nullable|in:horizontal,vertical',

            // İlişkiler
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'video başlığı',
            'description' => 'açıklama',
            'video' => 'video dosyası',
            'thumbnail' => 'kapak görseli',
            'is_premium' => 'premium durumu',
            'is_active' => 'aktiflik durumu',
            'orientation' => 'video yönelimi',
            'category_ids' => 'kategoriler',
            'tag_ids' => 'etiketler',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Video başlığı zorunludur.',
            'title.max' => 'Video başlığı en fazla 255 karakter olabilir.',

            'video.file' => 'Geçerli bir video dosyası seçiniz.',
            'video.mimetypes' => 'Video formatı MP4, MPEG, MOV veya AVI olmalıdır.',
            'video.max' => 'Video dosyası en fazla 500MB olabilir.',

            'thumbnail.image' => 'Kapak görseli bir resim dosyası olmalıdır.',
            'thumbnail.mimes' => 'Kapak görseli JPEG, PNG, JPG veya WEBP formatında olmalıdır.',
            'thumbnail.max' => 'Kapak görseli en fazla 5MB olabilir.',

            'orientation.in' => 'Video yönelimi yatay (horizontal) veya dikey (vertical) olmalıdır.',

            'category_ids.array' => 'Kategoriler dizi formatında olmalıdır.',
            'category_ids.*.exists' => 'Seçilen kategori bulunamadı.',

            'tag_ids.array' => 'Etiketler dizi formatında olmalıdır.',
            'tag_ids.*.exists' => 'Seçilen etiket bulunamadı.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Checkbox değerlerini boolean'a çevir
        $merge = [];

        if ($this->has('is_premium')) {
            $merge['is_premium'] = $this->boolean('is_premium');
        }

        if ($this->has('is_active')) {
            $merge['is_active'] = $this->boolean('is_active');
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}
