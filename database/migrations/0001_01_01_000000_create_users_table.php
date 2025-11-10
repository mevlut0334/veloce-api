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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Temel alanlar - String uzunlukları belirlendi
            $table->string('name', 100); // Uzunluk limiti performans için
            $table->string('email', 150)->unique();
            $table->string('phone', 20)->index(); // Unique yerine index (daha hızlı)
            $table->string('password', 255); // Bcrypt için yeterli
            $table->string('avatar', 500)->nullable(); // URL için yeterli uzunluk

            // Subscription alanları - Optimize edilmiş tipler
            $table->unsignedTinyInteger('subscription_type')->default(0)->comment('0=free, 1=premium');
            $table->timestamp('subscription_starts_at')->nullable()->index();
            $table->timestamp('subscription_ends_at')->nullable()->index();

            // Status alanları
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_activity_at')->nullable()->index();

            $table->rememberToken();
            $table->timestamps();

            // PERFORMANS İNDEXLERİ - Optimize edilmiş
            // Email unique zaten otomatik index
            // Phone index yukarıda eklendi

            // Composite indexler - En sık kullanılan sorgular için
            $table->index(['subscription_type', 'is_active', 'subscription_ends_at'], 'idx_subscription_status');
            $table->index(['is_active', 'last_activity_at'], 'idx_active_users');
            $table->index(['subscription_type', 'created_at'], 'idx_subscription_stats');
            $table->index(['email', 'is_active'], 'idx_login');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 150)->primary(); // Uzunluk limiti
            $table->string('token', 100); // Token uzunluğu
            $table->timestamp('created_at')->nullable()->index(); // Expired token cleanup için
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable()->index(); // IPv6 desteği
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->unsigned()->index();

            // Composite index - Aktif kullanıcı sorguları için
            $table->index(['user_id', 'last_activity'], 'idx_user_activity');
        });

        // MariaDB/MySQL için tablo seviyesi optimizasyonlar
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE users ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
            DB::statement('ALTER TABLE sessions ENGINE=InnoDB ROW_FORMAT=DYNAMIC');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
