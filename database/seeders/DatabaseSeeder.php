<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Test kullanıcı oluştur
        User::create([
            'name' => 'Test User',
            'email' => 'test@veloce.com',
            'password' => bcrypt('271369'),
            'is_active' => true,
            'is_admin' => false,
        ]);

        // Admin kullanıcı seeder'ını çalıştır
        $this->call([
            AdminUserSeeder::class,
        ]);
    }
}
