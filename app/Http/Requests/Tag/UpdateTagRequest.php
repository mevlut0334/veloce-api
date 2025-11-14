<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Yetki kontrolü route middleware'de yapılıyor
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tagId = $this->route('tag'); // Route parameter'dan tag id'sini al

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('tags', 'name')->ignore($tagId)
            ],
            'slug' => [
                'nullable',
                'string',
                'max:60',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tags', 'slug')->ignore($tagId)
            ],
            'description' => [
                'nullable',
                'string',
                'max:255'
            ],
            'is_active' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tag adı',
            'slug' => 'slug',
            'description' => 'açıklama',
            'is_active' => 'durum'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tag adı zorunludur.',
            'name.min' => 'Tag adı en az :min karakter olmalıdır.',
            'name.max' => 'Tag adı en fazla :max karakter olmalıdır.',
            'name.unique' => 'Bu tag adı zaten kullanılıyor.',
            'slug.regex' => 'Slug formatı geçersiz. Sadece küçük harf, rakam ve tire kullanılabilir.',
            'slug.unique' => 'Bu slug zaten kullanılıyor.',
            'description.max' => 'Açıklama en fazla :max karakter olmalıdır.'
        ];
    }
}
