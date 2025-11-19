<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Haftalık Plan',
                'duration_days' => 7,
                'price' => 29.99,
                'is_active' => true,
                'description' => '7 günlük deneme paketi - Tüm özelliklere erişim',
            ],
            [
                'name' => 'Aylık Plan',
                'duration_days' => 30,
                'price' => 99.99,
                'is_active' => true,
                'description' => '1 aylık standart paket - Tüm özelliklere erişim',
            ],
            [
                'name' => '3 Aylık Plan',
                'duration_days' => 90,
                'price' => 249.99,
                'is_active' => true,
                'description' => '3 aylık ekonomik paket - %17 indirimli',
            ],
            [
                'name' => '6 Aylık Plan',
                'duration_days' => 180,
                'price' => 449.99,
                'is_active' => true,
                'description' => '6 aylık avantajlı paket - %25 indirimli',
            ],
            [
                'name' => 'Yıllık Plan',
                'duration_days' => 365,
                'price' => 799.99,
                'is_active' => true,
                'description' => '1 yıllık premium paket - %33 indirimli',
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['name' => $plan['name']], // Aynı isimde plan varsa güncelle
                $plan
            );
        }

        $this->command->info('✅ Abonelik planları başarıyla oluşturuldu!');
    }
}
