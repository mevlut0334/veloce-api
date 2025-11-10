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
       Schema::create('home_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('image_path', 255);
            $table->unsignedBigInteger('video_id')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite index for active sliders ordered
            $table->index(['is_active', 'order']);
            $table->index('video_id');

            // Foreign key
            $table->foreign('video_id', 'fk_home_sliders_video')
                  ->references('id')->on('videos')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_sliders');
    }
};
