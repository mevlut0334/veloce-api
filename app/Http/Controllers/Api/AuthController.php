<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected UserServiceInterface $userService
    ) {}

    /**
     * Kullanıcı kaydı
     */
    public function register(StoreUserRequest $request): JsonResponse
    {
        try {
            $result = $this->userService->register($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Kayıt başarılı',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kayıt sırasında bir hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kullanıcı girişi
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $result = $this->userService->login($request->only('email', 'password'));

            return response()->json([
                'success' => true,
                'message' => 'Giriş başarılı',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Giriş bilgileri hatalı',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Giriş sırasında bir hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kullanıcı çıkışı
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->userService->logout($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Çıkış başarılı',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Çıkış sırasında bir hata oluştu',
            ], 500);
        }
    }

    /**
     * Kullanıcı profilini getir
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $profile = $this->userService->getProfile($request->user());

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => new UserResource($profile['user']),
                    'is_subscriber' => $profile['is_subscriber'],
                    'subscription_status' => $profile['subscription_status'],
                    'subscription_expiry' => $profile['subscription_expiry'],
                    'remaining_days' => $profile['remaining_days'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profil bilgileri alınamadı',
            ], 500);
        }
    }

    /**
     * Profil güncelleme
     */
    public function updateProfile(UpdateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->updateProfile(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Profil güncellendi',
                'data' => [
                    'user' => new UserResource($user)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profil güncellenemedi',
            ], 500);
        }
    }

    /**
     * Şifre değiştirme
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $this->userService->changePassword(
                $request->user(),
                $request->current_password,
                $request->password
            );

            return response()->json([
                'success' => true,
                'message' => 'Şifre değiştirildi',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mevcut şifre hatalı',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Şifre değiştirilemedi',
            ], 500);
        }
    }
}
