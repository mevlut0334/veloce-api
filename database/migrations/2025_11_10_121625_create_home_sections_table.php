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
       Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->enum('content_type', ['video_ids', 'category', 'trending', 'recent']);
            $table->text('content_data')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('limit')->default(20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite index for active sections ordered
            $table->index(['is_active', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
