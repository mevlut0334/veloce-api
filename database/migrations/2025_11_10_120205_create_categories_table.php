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
         Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 150)->unique();
            $table->string('description', 500)->nullable();
            $table->string('icon', 100)->nullable()->comment('icon class or path');
            $table->smallInteger('order')->unsigned()->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite index - aktif kategorileri sıralı göstermek için
            $table->index(['is_active', 'order']);

            // slug unique zaten index oluşturur, tekrar eklemeye gerek yok
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
