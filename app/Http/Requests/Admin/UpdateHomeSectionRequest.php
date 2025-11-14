<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomeSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Middleware'de kontrol ediliyor
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'content_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(HomeSection::CONTENT_TYPES),
            ],
            'content_data' => [
                'nullable',
                'array',
            ],
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:' . HomeSection::MAX_LIMIT,
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Content type'a göre content_data validasyonu
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Content type değiştiriliyorsa veya mevcut content type varsa
            $contentType = $this->input('content_type');

            // Eğer content_type gönderilmediyse, mevcut section'ın content_type'ını al
            if (!$contentType) {
                $section = $this->route('home_section');
                if ($section instanceof HomeSection) {
                    $contentType = $section->content_type;
                }
            }

            // Content data validasyonu yap
            if ($contentType && $this->has('content_data')) {
                $contentData = $this->input('content_data', []);
                $rules = $this->getContentDataRules($contentType);

                if (!empty($rules)) {
                    $contentValidator = \Validator::make($contentData, $rules);

                    if ($contentValidator->fails()) {
                        foreach ($contentValidator->errors()->messages() as $field => $messages) {
                            foreach ($messages as $message) {
                                $validator->errors()->add("content_data.{$field}", $message);
                            }
                        }
                    }
                }
            }

            // Content type değiştiriliyorsa ama content_data yoksa hata ver
            if ($this->has('content_type') && !$this->has('content_data')) {
                $newContentType = $this->input('content_type');

                // Sadece content_data gerektiren tipler için kontrol
                if (in_array($newContentType, [HomeSection::TYPE_VIDEO_IDS, HomeSection::TYPE_CATEGORY])) {
                    $validator->errors()->add(
                        'content_data',
                        'Content type değiştirildiğinde content_data gönderilmelidir.'
                    );
                }
            }
        });
    }

    /**
     * Content type'a göre content_data kurallarını döndür
     */
    private function getContentDataRules(string $contentType): array
    {
        return match($contentType) {
            HomeSection::TYPE_VIDEO_IDS => [
                'video_ids' => ['required', 'array', 'min:1'],
                'video_ids.*' => [
                    'required',
                    'integer',
                    'exists:videos,id',
                ],
            ],
            HomeSection::TYPE_CATEGORY => [
                'category_id' => [
                    'required',
                    'integer',
                    'exists:categories,id',
                ],
            ],
            HomeSection::TYPE_TRENDING => [
                'days' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:365',
                ],
            ],
            HomeSection::TYPE_RECENT => [],
            default => [],
        };
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Section başlığı zorunludur.',
            'title.max' => 'Section başlığı en fazla 100 karakter olabilir.',
            'content_type.required' => 'İçerik tipi zorunludur.',
            'content_type.in' => 'Geçersiz içerik tipi.',
            'limit.integer' => 'Limit sayı olmalıdır.',
            'limit.min' => 'Limit en az 1 olmalıdır.',
            'limit.max' => 'Limit en fazla ' . HomeSection::MAX_LIMIT . ' olabilir.',
            'is_active.boolean' => 'Aktiflik durumu geçersiz.',
            'order.integer' => 'Sıralama sayı olmalıdır.',
            'order.min' => 'Sıralama 0\'dan küçük olamaz.',
            'content_data.video_ids.required' => 'En az bir video seçmelisiniz.',
            'content_data.video_ids.array' => 'Video ID\'leri dizi formatında olmalıdır.',
            'content_data.video_ids.min' => 'En az bir video seçmelisiniz.',
            'content_data.video_ids.*.exists' => 'Seçilen videolardan biri bulunamadı.',
            'content_data.category_id.required' => 'Kategori seçmelisiniz.',
            'content_data.category_id.exists' => 'Seçilen kategori bulunamadı.',
            'content_data.days.integer' => 'Gün sayısı sayı olmalıdır.',
            'content_data.days.min' => 'Gün sayısı en az 1 olmalıdır.',
            'content_data.days.max' => 'Gün sayısı en fazla 365 olabilir.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // is_active checkbox olarak gelebilir
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // content_data JSON string olarak gelebilir
        if ($this->has('content_data') && is_string($this->content_data)) {
            $this->merge([
                'content_data' => json_decode($this->content_data, true) ?? [],
            ]);
        }
    }
}
