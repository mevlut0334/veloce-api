<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreUserRequest extends FormRequest
{
    /**
     * Kullanıcı bu isteği yapabilir mi?
     */
    public function authorize(): bool
    {
        return true; // Herkes kayıt olabilir
    }

    /**
     * Validasyon kuralları
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * Özel hata mesajları
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ad soyad alanı zorunludur',
            'name.max' => 'Ad soyad en fazla 255 karakter olabilir',

            'email.required' => 'E-posta adresi zorunludur',
            'email.email' => 'Geçerli bir e-posta adresi giriniz',
            'email.unique' => 'Bu e-posta adresi zaten kullanılıyor',

            'phone.required' => 'Telefon numarası zorunludur',
            'phone.unique' => 'Bu telefon numarası zaten kullanılıyor',

            'password.required' => 'Şifre zorunludur',
            'password.min' => 'Şifre en az 6 karakter olmalıdır',
            'password.confirmed' => 'Şifre onayı eşleşmiyor',
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
