<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * Yeni kullanıcı oluştur
     */
    public function create(array $data): User;

    /**
     * ID ile kullanıcı bul
     */
    public function find(int $id): ?User;

    /**
     * Email ile kullanıcı bul
     */
    public function findByEmail(string $email): ?User;

    /**
     * Telefon ile kullanıcı bul
     */
    public function findByPhone(string $phone): ?User;

    /**
     * Kullanıcı güncelle
     */
    public function update(User $user, array $data): bool;

    /**
     * Şifre güncelle
     */
    public function updatePassword(User $user, string $password): bool;

    /**
     * Son aktivite zamanını güncelle
     */
    public function updateLastActivity(User $user): bool;

    /**
     * Kullanıcı sil
     */
    public function delete(User $user): bool;

    /**
     * Tüm kullanıcıları getir (pagination)
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator;

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
     * Kullanıcı durumunu değiştir (aktif/pasif)
     */
    public function toggleStatus(User $user): bool;

    /**
     * Kullanıcı istatistiklerini getir
     */
    public function getUserStats(User $user): array;

    /**
     * Son aktivite tarihine göre kullanıcıları getir
     */
    public function getByRecentActivity(int $days = 30, int $perPage = 15): LengthAwarePaginator;

    /**
     * Kullanıcı sayısını getir (filtreye göre)
     */
    public function count(array $filters = []): int;
}
