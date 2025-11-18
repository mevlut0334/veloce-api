<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_category_buttons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('position')->comment('1 veya 2'); // 1: Sol buton, 2: Sağ buton
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Sadece 2 kayıt olabilir (position 1 ve 2)
            $table->unique('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_category_buttons');
    }
};
