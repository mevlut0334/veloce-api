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
        Schema::create('tag_video', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('video_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite unique index - hem constraint hem index görevi görür
            $table->unique(['tag_id', 'video_id']);

            // Video'ya göre tag'leri listeleme için
            $table->index('video_id');

            // Foreign keys - constraint name ile ve WITHOUT index (zaten var)
            $table->foreign('tag_id', 'fk_tag_video_tag')
                  ->references('id')->on('tags')
                  ->onDelete('cascade');

            $table->foreign('video_id', 'fk_tag_video_video')
                  ->references('id')->on('videos')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tag_video');
    }
};
