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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Ödeme tutarı
            $table->string('currency', 3)->default('TRY'); // Para birimi
            $table->string('payment_method'); // Ödeme yöntemi (credit_card, paypal, vb)
            $table->string('transaction_id')->unique(); // Benzersiz işlem ID'si
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('payment_details')->nullable(); // JSON olarak ödeme detayları
            $table->timestamp('paid_at')->nullable(); // Ödeme tarihi
            $table->timestamps();

            // Performans için indexler
            $table->index('user_id');
            $table->index('status');
            $table->index('paid_at');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
