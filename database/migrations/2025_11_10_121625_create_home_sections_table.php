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
            $table->string('title'); // Bölüm başlığı (örn: "En Çok İzlenenler")
            $table->enum('content_type', ['video_ids', 'category', 'trending', 'recent']); // İçerik tipi
            $table->text('content_data')->nullable(); // video_ids için JSON array, category için category_id
            $table->integer('order')->default(0); // Sıralama
            $table->boolean('is_active')->default(true); // Aktif/Pasif
            $table->integer('limit')->default(20); // Gösterilecek video sayısı
            $table->timestamps();
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
