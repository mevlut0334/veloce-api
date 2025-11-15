<?php
// database/seeders/AdminUserSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // İlk admin kullanıcıyı oluştur
        User::create([
            'name' => 'Admin',
            'email' => 'admin@veloce.com',
            'password' => Hash::make('271369'), // Şifreyi değiştirin!
            'is_admin' => true,
            'is_active' => true,
        ]);

        $this->command->info('✅ Admin kullanıcı başarıyla oluşturuldu!');
        $this->command->info('📧 Email: admin@veloce.com');
        $this->command->info('🔑 Şifre: 271369');
    }
}
