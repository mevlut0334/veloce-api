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
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('video_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->unsignedMediumInteger('watch_duration')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('viewed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite indexes
            $table->index(['video_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
            $table->index(['video_id', 'is_completed']);

            // Foreign keys
            $table->foreign('video_id', 'fk_video_views_video')
                  ->references('id')->on('videos')
                  ->onDelete('cascade');

            $table->foreign('user_id', 'fk_video_views_user')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_views');
    }
};
