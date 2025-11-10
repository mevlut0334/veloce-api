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
       Schema::create('user_playlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100); // String length limit
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Composite index - user'ın public/private playlist'lerini filtrelemek için
            $table->index(['user_id', 'is_public']);

            // Public playlist'leri listelemek için (is_public filtreleme öncelikli)
            $table->index(['is_public', 'created_at']);

            // Foreign key
            $table->foreign('user_id', 'fk_user_playlists_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_playlists');
    }
};
