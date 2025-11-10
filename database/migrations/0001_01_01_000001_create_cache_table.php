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
        Schema::create('cache', function (Blueprint $table) {
            // Primary key - VARCHAR yerine daha performanslı
            $table->string('key', 255)->primary();

            // Value - mediumText yerine longText (büyük cache değerleri için)
            $table->longText('value');

            // Expiration - INDEX eklenmeli (cleanup query'leri için kritik)
            $table->integer('expiration')->unsigned()->index();
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 255)->primary();
            $table->string('owner', 255);

            // Lock expiration için index (expired lock temizliği için)
            $table->integer('expiration')->unsigned()->index();
        });

        // MySQL için tablo optimizasyonları
        if (DB::getDriverName() === 'mysql') {
            // InnoDB engine ve kompresyon
            DB::statement('ALTER TABLE cache ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
            DB::statement('ALTER TABLE cache_locks ENGINE=InnoDB ROW_FORMAT=DYNAMIC');

            // Partition (opsiyonel - büyük veri için)
            // Her ay için ayrı partition oluşturabilirsiniz
            // DB::statement("
            //     ALTER TABLE cache PARTITION BY RANGE (expiration) (
            //         PARTITION p_old VALUES LESS THAN (UNIX_TIMESTAMP('2024-01-01')),
            //         PARTITION p_current VALUES LESS THAN MAXVALUE
            //     )
            // ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
    }
};
