<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomeSetting;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
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
        $dataSolutions = Category::firstOrCreate(['name' => 'Data Solutions'], ['image' => '/assets/images/categories/data.png']);

        // 3.1 Digital Products (Persistent)
        Product::firstOrCreate(
            ['name' => 'Once-off Dataset'],
            [
                'description' => 'Get the raw data without analytics or insights and use it for your own purposes. Ideal for AI training or researchers who just want the data.',
                'price' => 5000.00,
                'image' => '/assets/images/products/dataset.png',
                'stock' => 9999,
                'category_id' => $dataSolutions->id,
            ]
        );

        Product::firstOrCreate(
            ['name' => 'Developer API'],
            [
                'description' => 'Power your custom applications with direct access to our structured legal dataset API.',
                'price' => 380.00,
                'image' => '/assets/images/products/api.png',
                'stock' => 9999,
                'category_id' => $dataSolutions->id,
            ]
        );

        Product::firstOrCreate(
            ['name' => 'Analytics Dashboard'],
            [
                'description' => 'No code required. Access to trends and insights through our analytics platform.',
                'price' => 3800.00,
                'image' => '/assets/images/products/analytics.png',
                'stock' => 9999,
                'category_id' => $dataSolutions->id,
            ]
        );

        // 5. Home Settings (Persistent)
        HomeSetting::firstOrCreate(['key' => 'hero_slider'], ['value' => [
            [
                // 'product_id' => null,
                // 'title' => '*TITEL OF THE PRODUKT*',
                // 'price' => '*PRICE*',
                // 'category' => '*CATEGORIES MEN/WOMEN*',
                // 'image' => null,
                // 'brand_ids' => [],
                // 'is_active' => true,
            ],
        ]]);

        HomeSetting::firstOrCreate(['key' => 'about_us'], ['value' => [
            // 'title_line_1' => '*WRITE YOUR',
            // 'title_line_2' => 'STORY LINE*',
            // 'description' => '*WRITE YOUR STORY OF YOUR BRAND BE YOURSELF*',
            // 'image' => null,
            // 'stats' => [
            //     ['label' => 'Years in treg', 'value' => '*00*'],
            //     ['label' => 'Pleasing Costumers', 'value' => '*00*'],
            // ],
        ]]);
    }
}
