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
         Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('slug', 60)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            // Aktif tagleri kullanım sayısına göre sırala
            $table->index(['is_active', 'usage_count']);

            // slug unique zaten index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
