<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserServiceInterface $userService
    ) {}

    /**
     * Tüm kullanıcıları listele
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Pagination parametresi
            $perPage = $request->get('per_page', 15);

            $users = $this->userService->getAllUsers($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcılar başarıyla getirildi',
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcılar getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Yeni kullanıcı oluştur
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı başarıyla oluşturuldu',
                'data' => new UserResource($user)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı oluşturulamadı',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tek kullanıcı detayını getir
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->userService->findUser((int) $id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı detayı getirildi',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı detayı getirilemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kullanıcı güncelle
     *
     * @param UpdateUserRequest $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            // Önce kullanıcıyı bul
            $user = $this->userService->findUser((int) $id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı'
                ], 404);
            }

            // Kullanıcıyı güncelle
            $updatedUser = $this->userService->updateUser($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı başarıyla güncellendi',
                'data' => new UserResource($updatedUser)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı güncellenemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kullanıcı sil
     *
     * @param string $id
     * @return JsonResponse
     */
    public function delete(string $id): JsonResponse
    {
        try {
            // Önce kullanıcıyı bul
            $user = $this->userService->findUser((int) $id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı'
                ], 404);
            }

            // Kullanıcıyı sil
            $this->userService->deleteUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Kullanıcı başarıyla silindi'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı silinemedi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
