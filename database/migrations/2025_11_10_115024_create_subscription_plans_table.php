<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->default('Yıllık Premium');
            $table->decimal('price', 10, 2)->unsigned();
            $table->smallInteger('duration_days')->unsigned()->default(365);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Aktif planları hızlı sorgulamak için
            $table->index(['is_active', 'price']);
            // Plan adı aramaları için (opsiyonel - ihtiyaç varsa)
            // $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
