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
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_path'); // storage'da dosya yolu
            $table->string('thumbnail_path'); // storage'da görsel yolu
            $table->enum('orientation', ['horizontal', 'vertical'])->default('horizontal');
            $table->boolean('is_premium')->default(false);
            $table->integer('duration')->nullable(); // saniye cinsinden
            $table->bigInteger('view_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_premium');
            $table->index('is_active');
            $table->index('orientation');
            $table->index('view_count');
            $table->index('created_at');
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
