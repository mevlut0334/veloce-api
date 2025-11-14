<?php

namespace App\Repositories;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TagRepository implements TagRepositoryInterface
{
    /**
     * Tüm aktif tag'leri getir
     */
    public function getAllActive(): Collection
    {
        return Tag::getActiveTags();
    }

    /**
     * ID ile tag bul
     */
    public function findById(int $id): ?Tag
    {
        return Cache::remember(
            "tag_find_{$id}",
            now()->addHours(2),
            fn() => Tag::find($id)
        );
    }

    /**
     * Slug ile tag bul
     */
    public function findBySlug(string $slug): ?Tag
    {
        return Cache::remember(
            "tag_slug_{$slug}",
            now()->addHours(2),
            fn() => Tag::where('slug', $slug)->first()
        );
    }

    /**
     * Popüler tag'leri getir
     */
    public function getPopular(int $limit = 20): Collection
    {
        return Tag::getPopularTags($limit);
    }

    /**
     * Tag bulutu için tag'leri getir
     */
    public function getTagCloud(int $limit = 50): Collection
    {
        return Tag::getTagCloud();
    }

    /**
     * Arama yap
     */
    public function search(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return Tag::search($query)
            ->withActiveVideosCount()
            ->alphabetical()
            ->paginate($perPage);
    }

    /**
     * Admin için tag'leri getir (sayfalı)
     */
    public function getForAdmin(int $perPage = 50): LengthAwarePaginator
    {
        return Tag::forAdmin()->paginate($perPage);
    }

    /**
     * Tag oluştur
     */
    public function create(array $data): Tag
    {
        return Tag::create($data);
    }

    /**
     * Tag güncelle
     */
    public function update(int $id, array $data): bool
    {
        $tag = Tag::findOrFail($id);

        return $tag->update($data);
    }

    /**
     * Tag sil
     */
    public function delete(int $id): bool
    {
        $tag = Tag::findOrFail($id);

        return $tag->delete();
    }

    /**
     * İsme göre tag bul veya oluştur
     */
    public function findOrCreateByName(string $name): Tag
    {
        return Tag::findOrCreateByName($name);
    }

    /**
     * Birden fazla tag bul veya oluştur
     */
    public function findOrCreateMany(array $names): Collection
    {
        $tags = new Collection();

        foreach ($names as $name) {
            $tags->push($this->findOrCreateByName(trim($name)));
        }

        return $tags;
    }

    /**
     * Tag istatistiklerini getir
     */
    public function getStats(): array
    {
        return Tag::getTagStats();
    }

    /**
     * Aktif/Pasif durumunu değiştir
     */
    public function toggleStatus(int $id): bool
    {
        $tag = Tag::findOrFail($id);
        $tag->is_active = !$tag->is_active;

        return $tag->save();
    }

    /**
     * Tag'a ait videoları getir
     */
    public function getVideos(int $tagId, int $perPage = 24): LengthAwarePaginator
    {
        $tag = Tag::findOrFail($tagId);

        return $tag->activeVideos()
            ->with(['categories:id,name,slug'])
            ->withCount('views')
            ->orderByDesc('videos.created_at')
            ->paginate($perPage);
    }

    /**
     * Kullanılmayan tag'leri getir
     */
    public function getUnused(): Collection
    {
        return Tag::withCount('videos')
            ->having('videos_count', '=', 0)
            ->alphabetical()
            ->get();
    }

    /**
     * Kullanılmayan tag'leri sil
     */
    public function deleteUnused(): int
    {
        return Tag::whereDoesntHave('videos')->delete();
    }

    /**
     * Cache temizle
     */
    public function clearCache(): void
    {
        Tag::clearAllCache();

        // Tek tek tag cache'lerini de temizle
        Cache::tags(['tags'])->flush();
    }
}
