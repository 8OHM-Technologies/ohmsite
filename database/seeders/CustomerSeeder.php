<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Arben Gashi', 'Besnik Hoxha', 'Dardan Krasniqi', 'Edona Berisha', 'Fatmir Leka',
            'Gresa Morina', 'Hekuran Shala', 'Ilirida Zeneli', 'Jeton Bytyqi', 'Kujtim Rama',
            'Liridona Kelmendi', 'Mergim Aliu', 'Nora Rugova', 'Olti Hoti', 'Pranvera Syla',
            'Qendrim Thaqi', 'Rina Mehmeti', 'Sali Rexhepi', 'Teuta Osmani', 'Valdrin Kuqi'
        ];

        foreach ($names as $name) {
            $totalSpent = rand(50, 2500);
            \App\Models\User::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'user',
                'status' => rand(0, 10) > 8 ? 'banned' : 'active',
                'total_spent' => $totalSpent,
                'orders_count' => rand(1, 15),
                'last_active_at' => now()->subDays(rand(0, 30)),
            ]);
        }
    }
}
