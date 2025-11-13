<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // SEO için slug kolonu
            $table->string('slug', 255)->unique()->after('title');

            // Video metadata kolonları
            $table->string('resolution', 20)->nullable()->after('duration')
                ->comment('Örn: 1920x1080, 1080x1920');

            $table->unsignedBigInteger('file_size')->nullable()->after('resolution')
                ->comment('Dosya boyutu (bytes)');

            // İşlem durumu takibi
            $table->boolean('is_processed')->default(false)->after('is_active')
                ->comment('Video işleme tamamlandı mı?');

            // Favori sayacı (performans için cache)
            $table->unsignedInteger('favorite_count')->default(0)->after('view_count');

            // Performans için yeni indexler
            $table->index('slug');
            $table->index(['is_active', 'is_processed']); // Aktif ve işlenmiş videolar
            $table->index(['is_premium', 'is_active', 'favorite_count']); // Premium popüler videolar
            $table->index('file_size'); // Dosya boyutu sorguları için
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            // Indexleri kaldır
            $table->dropIndex(['slug']);
            $table->dropIndex(['is_active', 'is_processed']);
            $table->dropIndex(['is_premium', 'is_active', 'favorite_count']);
            $table->dropIndex(['file_size']);

            // Kolonları kaldır
            $table->dropColumn([
                'slug',
                'resolution',
                'file_size',
                'is_processed',
                'favorite_count'
            ]);
        });
    }
};
