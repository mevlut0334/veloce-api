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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable()->unique();
            $table->timestamps();

            // Composite indexes - en sık kullanılan sorgular için
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'expires_at']);
            $table->index(['status', 'expires_at']);

            // Unique constraint - bir kullanıcının aynı anda sadece 1 aktif aboneliği
            $table->unique(['user_id', 'status'], 'unique_active_subscription')
                  ->where('status', 'active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
