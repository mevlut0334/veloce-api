<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Yeni kullanıcı oluştur
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * ID ile kullanıcı bul
     */
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Email ile kullanıcı bul
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Kullanıcı güncelle
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Şifre güncelle
     */
    public function updatePassword(User $user, string $password): bool
    {
        return $user->update([
            'password' => Hash::make($password)
        ]);
    }

    /**
     * Son aktivite zamanını güncelle
     */
    public function updateLastActivity(User $user): bool
    {
        return $user->update([
            'last_activity_at' => now()
        ]);
    }

    /**
     * Kullanıcı sil
     */
    public function delete(User $user): bool
    {
        // Cache temizle
        $user->clearSubscriptionCache();

        return $user->delete();
    }

    /**
     * Tüm kullanıcıları getir (pagination)
     */
    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return User::select(['id', 'name', 'email', 'is_active', 'last_activity_at', 'created_at'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Aktif kullanıcıları getir
     */
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::select(['id', 'name', 'email', 'is_active', 'last_activity_at', 'created_at'])
            ->active()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Pasif kullanıcıları getir
     */
    public function getInactiveUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::select(['id', 'name', 'email', 'is_active', 'last_activity_at', 'created_at'])
            ->inactive()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Abone olan kullanıcıları getir
     */
    public function getSubscribers(int $perPage = 15): LengthAwarePaginator
    {
        return User::select(['id', 'name', 'email', 'is_active', 'last_activity_at', 'created_at'])
            ->subscribers()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Abone olmayan kullanıcıları getir
     */
    public function getNonSubscribers(int $perPage = 15): LengthAwarePaginator
    {
        return User::select(['id', 'name', 'email', 'is_active', 'last_activity_at', 'created_at'])
            ->nonSubscribers()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Kullanıcı durumunu değiştir (aktif/pasif)
     */
    public function toggleStatus(User $user): bool
    {
        $user->clearSubscriptionCache();

        return $user->update([
            'is_active' => !$user->is_active
        ]);
    }

    /**
     * Kullanıcı istatistiklerini getir
     */
    public function getUserStats(User $user): array
    {
        return $user->getStats();
    }

    /**
     * Son aktivite tarihine göre kullanıcıları getir
     */
    public function getByRecentActivity(int $days = 30, int $perPage = 15): LengthAwarePaginator
    {
        return User::select(['id', 'name', 'email', 'is_active', 'last_activity_at', 'created_at'])
            ->recentActivity($days)
            ->latest('last_activity_at')
            ->paginate($perPage);
    }

    /**
     * Kullanıcı sayısını getir (filtreye göre)
     */
    public function count(array $filters = []): int
    {
        $query = User::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_subscriber'])) {
            $filters['is_subscriber']
                ? $query->subscribers()
                : $query->nonSubscribers();
        }

        if (isset($filters['recent_days'])) {
            $query->recentActivity($filters['recent_days']);
        }

        return $query->count();
    }
}
