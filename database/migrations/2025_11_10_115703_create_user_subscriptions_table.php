<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');

            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('active');
            $table->enum('subscription_type', ['manual', 'paid', 'trial'])->default('manual');

            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable()->unique();

            // Admin tarafından oluşturuldu mu?
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable()->comment('Admin notu veya iptal sebebi');

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'expires_at']);
            $table->index(['status', 'expires_at']);
            $table->index(['subscription_type']);
            $table->index(['starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
