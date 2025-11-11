<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function create(array $data): User;
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function update(User $user, array $data): bool;
    public function updatePassword(User $user, string $password): bool;
    public function updateLastActivity(User $user): bool;
    public function delete(User $user): bool;
    public function getAll(int $perPage = 15): LengthAwarePaginator;
    public function getActiveUsers(int $perPage = 15): LengthAwarePaginator;
    public function getInactiveUsers(int $perPage = 15): LengthAwarePaginator;
    public function getSubscribers(int $perPage = 15): LengthAwarePaginator;
    public function getNonSubscribers(int $perPage = 15): LengthAwarePaginator;
    public function toggleStatus(User $user): bool;
    public function getUserStats(User $user): array;
    public function getByRecentActivity(int $days = 30, int $perPage = 15): LengthAwarePaginator;
    public function count(array $filters = []): int;
}
