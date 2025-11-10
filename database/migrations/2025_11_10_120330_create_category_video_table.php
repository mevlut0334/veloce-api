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
         Schema::create('category_video', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');

            // Composite primary key - id kolonuna gerek yok
            $table->primary(['category_id', 'video_id']);

            // Ters yönlü sorgular için (video->categories)
            $table->index('video_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_video');
    }
};
