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
         Schema::create('playlist_video', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_playlist_id');
            $table->unsignedBigInteger('video_id');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite unique + ordering index
            $table->unique(['user_playlist_id', 'video_id']);
            $table->index(['user_playlist_id', 'order']);
            $table->index('video_id');

            // Foreign keys
            $table->foreign('user_playlist_id', 'fk_playlist_video_playlist')
                  ->references('id')->on('user_playlists')
                  ->onDelete('cascade');

            $table->foreign('video_id', 'fk_playlist_video_video')
                  ->references('id')->on('videos')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlist_video');
    }
};
