<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Kullanıcı bu isteği yapabilir mi?
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validasyon kuralları
     */
    public function rules(): array
    {
        $userId = $this->route('user')
            ? $this->route('user')->id
            : $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Özel hata mesajları
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Ad soyad en fazla 255 karakter olabilir',

            'email.email' => 'Geçerli bir e-posta adresi giriniz',
            'email.unique' => 'Bu e-posta adresi zaten kullanılıyor',

            'phone.unique' => 'Bu telefon numarası zaten kullanılıyor',

            'is_active.boolean' => 'Durum değeri geçersiz',
        ];
    }

    /**
     * Validasyon başarısız olduğunda JSON response döndür
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validasyon hatası',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
