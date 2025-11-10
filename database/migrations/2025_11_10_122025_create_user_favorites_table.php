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
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('video_id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite unique index
            $table->unique(['user_id', 'video_id']);
            $table->index('video_id');

            // Foreign keys
            $table->foreign('user_id', 'fk_user_favorites_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('video_id', 'fk_user_favorites_video')
                  ->references('id')->on('videos')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};
