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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 191)->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');

            // Composite index - en sık kullanılan sorgular için
            $table->index(['queue', 'reserved_at']);
            // Available jobs için optimize edilmiş index
            $table->index(['queue', 'available_at', 'reserved_at']);
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('name', 191);
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();

            // Batch durumunu sorgulamak için
            $table->index(['created_at', 'finished_at']);
            // Pending jobs filtreleme için
            $table->index('pending_jobs');
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 191)->unique();
            $table->string('connection', 191);
            $table->string('queue', 191);
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();

            // Failed jobs filtreleme için
            $table->index(['queue', 'failed_at']);
            $table->index('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
