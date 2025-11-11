<?php

namespace App\Services\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    /**
     * Kullanıcı kaydı (Self registration)
     */
    public function register(array $data): array;

    /**
     * Kullanıcı girişi
     */
    public function login(array $credentials): array;

    /**
     * Kullanıcı çıkışı
     */
    public function logout(User $user): bool;

    /**
     * Kullanıcı profili getir
     */
    public function getProfile(User $user): array;

    /**
     * Profil güncelle
     */
    public function updateProfile(User $user, array $data): User;

    /**
     * Şifre değiştir (Kullanıcı kendi şifresini değiştirir)
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool;

    /**
     * Admin tarafından şifre güncelle
     */
    public function updatePasswordByAdmin(User $user, string $newPassword): bool;

    /**
     * ID ile kullanıcı bul
     */
    public function findUser(int $id): ?User;

    /**
     * Tüm kullanıcıları getir
     */
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Aktif kullanıcıları getir
     */
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Pasif kullanıcıları getir
     */
    public function getInactiveUsers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Abone olan kullanıcıları getir
     */
    public function getSubscribers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Abone olmayan kullanıcıları getir
     */
    public function getNonSubscribers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Kullanıcı oluştur (Admin tarafından)
     */
    public function createUser(array $data): User;

    /**
     * Kullanıcı güncelle (Admin tarafından)
     */
    public function updateUser(User $user, array $data): User;

    /**
     * Kullanıcı sil
     */
    public function deleteUser(User $user): bool;

    /**
     * Kullanıcı durumunu değiştir (aktif/pasif)
     */
    public function toggleUserStatus(User $user): bool;

    /**
     * Kullanıcı istatistiklerini getir
     */
    public function getUserStats(User $user): array;

    /**
     * Son aktivite tarihine göre kullanıcıları getir
     */
    public function getRecentActiveUsers(int $days = 30, int $perPage = 15): LengthAwarePaginator;

    /**
     * Kullanıcı sayılarını getir (dashboard için)
     */
    public function getUserCounts(): array;
}
