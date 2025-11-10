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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subscription_plan_id');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('TRY');
            $table->string('payment_method', 50);
            $table->string('transaction_id', 100)->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->text('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite indexes
            $table->index(['user_id', 'status', 'paid_at']);
            $table->index(['status', 'paid_at']);
            $table->index('subscription_plan_id');

            // Foreign keys
            $table->foreign('user_id', 'fk_payments_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('subscription_plan_id', 'fk_payments_plan')
                  ->references('id')->on('subscription_plans')
                  ->onDelete('cascade');
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
