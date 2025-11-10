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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('video_path', 500);
            $table->string('thumbnail_path', 500);
            $table->boolean('orientation')->default(0)->comment('0=horizontal, 1=vertical');
            $table->boolean('is_premium')->default(false);
            $table->mediumInteger('duration')->unsigned()->nullable()->comment('seconds');
            $table->unsignedInteger('view_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite indexes - gerçek kullanım senaryoları için
            // Ana sayfa: Aktif, horizontal videolar
            $table->index(['is_active', 'orientation', 'created_at']);

            // Premium filtreleme: Aktif premium videolar
            $table->index(['is_active', 'is_premium', 'view_count']);

            // Trending/Popular: Aktif videolar görüntülenmeye göre
            $table->index(['is_active', 'view_count']);

            // Fulltext search (opsiyonel - ihtiyaç varsa)
            // $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
