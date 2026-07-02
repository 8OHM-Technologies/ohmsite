<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\HomeSetting;
use Illuminate\Support\Facades\Hash;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Regular User
        User::firstOrCreate(['email' => 'client@gmail.com'], [
            'name' => 'Test Client',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // 3. Categories (Persistent)
        $men = Category::firstOrCreate(['name' => 'Men'], ['image' => '/assets/images/categories/men.png']);
        $women = Category::firstOrCreate(['name' => 'Women'], ['image' => '/assets/images/categories/women.png']);

        // 4. Brands (Persistent - these will always be there)
        $brands = [
            ['name' => 'Nike', 'logo' => '/assets/images/brands/Nike.png'],
            ['name' => 'Jordan', 'logo' => '/assets/images/brands/Jordan.png'],
            ['name' => 'Adidas', 'logo' => '/assets/images/brands/Adidas.png'],
            ['name' => 'Gucci', 'logo' => '/assets/images/brands/Gucci.png'],
            ['name' => 'Hermes', 'logo' => '/assets/images/brands/Hermes.png'],
            ['name' => 'Chanel', 'logo' => '/assets/images/brands/Chanel.png'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand['name']], ['logo' => $brand['logo']]);
        }

        // 5. Home Settings (Persistent)
        HomeSetting::firstOrCreate(['key' => 'hero_slider'], ['value' => [
            [
                'product_id' => null,
                'title' => '*TITEL OF THE PRODUKT*',
                'price' => '*PRICE*',
                'category' => '*CATEGORIES MEN/WOMEN*',
                'image' => null,
                'brand_ids' => [],
                'is_active' => true,
            ]
        ]]);

        HomeSetting::firstOrCreate(['key' => 'about_us'], ['value' => [
            'title_line_1' => '*WRITE YOUR',
            'title_line_2' => 'STORY LINE*',
            'description' => '*WRITE YOUR STORY OF YOUR BRAND BE YOURSELF*',
            'image' => null,
            'stats' => [
                ['label' => 'Years in treg', 'value' => '*00*'],
                ['label' => 'Pleasing Costumers', 'value' => '*00*'],
            ]
        ]]);
    }
}
