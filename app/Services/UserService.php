<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Kullanıcı kaydı (Self registration)
     */
    public function register(array $data): array
    {
        $user = $this->userRepository->create($data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /**
     * Kullanıcı girişi
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Giriş bilgileri hatalı'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'account' => ['Hesabınız aktif değil'],
            ]);
        }

        // Son aktivite zamanını güncelle
        $this->userRepository->updateLastActivity($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->fresh(),
            'token' => $token
        ];
    }

    /**
     * Kullanıcı çıkışı
     */
    public function logout(User $user): bool
    {
        return $user->currentAccessToken()->delete();
    }

    /**
     * Kullanıcı profili getir
     */
    public function getProfile(User $user): array
    {
        $user->load('activeSubscription');

        return [
            'user' => $user,
            'is_subscriber' => $user->isSubscriber(),
            'subscription_status' => $user->subscriptionStatus(),
            'subscription_expiry' => $user->subscriptionExpiry(),
            'remaining_days' => $user->remainingSubscriptionDays(),
        ];
    }

    /**
     * Profil güncelle
     */
    public function updateProfile(User $user, array $data): User
    {
        $this->userRepository->update($user, $data);

        return $user->fresh();
    }

    /**
     * Şifre değiştir (Kullanıcı kendi şifresini değiştirir)
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mevcut şifre hatalı'],
            ]);
        }

        return $this->userRepository->updatePassword($user, $newPassword);
    }

    /**
     * Admin tarafından şifre güncelle
     */
    public function updatePasswordByAdmin(User $user, string $newPassword): bool
    {
        return $this->userRepository->updatePassword($user, $newPassword);
    }

    /**
     * ID ile kullanıcı bul
     */
    public function findUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Tüm kullanıcıları getir
     */
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getAll($perPage);
    }

    /**
     * Aktif kullanıcıları getir
     */
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getActiveUsers($perPage);
    }

    /**
     * Pasif kullanıcıları getir
     */
    public function getInactiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getInactiveUsers($perPage);
    }

    /**
     * Abone olan kullanıcıları getir
     */
    public function getSubscribers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getSubscribers($perPage);
    }

    /**
     * Abone olmayan kullanıcıları getir
     */
    public function getNonSubscribers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getNonSubscribers($perPage);
    }

    /**
     * Kullanıcı oluştur (Admin tarafından)
     */
    public function createUser(array $data): User
    {
        return $this->userRepository->create($data);
    }

    /**
     * Kullanıcı güncelle (Admin tarafından)
     */
    public function updateUser(User $user, array $data): User
    {
        $this->userRepository->update($user, $data);

        return $user->fresh();
    }

    /**
     * Kullanıcı sil
     */
    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }

    /**
     * Kullanıcı durumunu değiştir (aktif/pasif)
     */
    public function toggleUserStatus(User $user): bool
    {
        return $this->userRepository->toggleStatus($user);
    }

    /**
     * Kullanıcı istatistiklerini getir
     */
    public function getUserStats(User $user): array
    {
        return $this->userRepository->getUserStats($user);
    }

    /**
     * Son aktivite tarihine göre kullanıcıları getir
     */
    public function getRecentActiveUsers(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getByRecentActivity($days, $perPage);
    }

    /**
     * Kullanıcı sayılarını getir (dashboard için)
     */
    public function getUserCounts(): array
    {
        return [
            'total' => $this->userRepository->count(),
            'active' => $this->userRepository->count(['is_active' => true]),
            'inactive' => $this->userRepository->count(['is_active' => false]),
            'subscribers' => $this->userRepository->count(['is_subscriber' => true]),
            'non_subscribers' => $this->userRepository->count(['is_subscriber' => false]),
            'recent_30_days' => $this->userRepository->count(['recent_days' => 30]),
            'recent_7_days' => $this->userRepository->count(['recent_days' => 7]),
        ];
    }
}
