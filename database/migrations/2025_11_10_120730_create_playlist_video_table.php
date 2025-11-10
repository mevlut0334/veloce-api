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
            $table->foreignId('user_playlist_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0); // playlist içinde sıralama
            $table->timestamps();

            // Unique constraint
            $table->unique(['user_playlist_id', 'video_id']);

            // Indexes
            $table->index('user_playlist_id');
            $table->index('video_id');
            $table->index('order');
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
