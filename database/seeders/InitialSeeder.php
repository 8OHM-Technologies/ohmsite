<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dataset;
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
            ['email' => env('ADMIN_EMAIL')],
            [
                'first_name' => 'Tiaan',
                'last_name' => 'Fourie',
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Regular User
        User::firstOrCreate(['email' => 'client@8ohm.co.za'], [
            'first_name' => 'Test',
            'last_name' => 'Client',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // 2.1 Subscribed User
        User::firstOrCreate(['email'=> 'subscriber@8ohm.co.za'], [
            'first_name' => 'Test',
            'last_name' => 'Subscriber',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // 2.2 Managed Pipeline User
        User::firstOrCreate(['email'=> 'pipeline@8ohm.co.za'], [
            'first_name' => 'Test',
            'last_name' => 'Pipeline',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // 3. Categories (Persistent)
        $dataSolutions = Category::firstOrCreate(['name' => 'Data Solutions'], ['image' => '/assets/images/categories/data.png']);

        // 3.1 Datasets (Persistent)
        Dataset::updateOrCreate(
            ['slug' => 'ccma'],
            [
                'name' => 'CCMA Arbitration Awards',
                'description' => 'Over 20 years of sanitized, structured CCMA arbitration awards.',
                'is_active' => true,
            ]
        );

        Dataset::updateOrCreate(
            ['slug' => 'labour-court'],
            [
                'name' => 'Labour Court Judgments',
                'description' => 'Sanitized, structured Labour Court judgments with regular updates.',
                'is_active' => true,
            ]
        );

        // 3.2 Digital Products (Persistent)
        Product::updateOrCreate(
            ['slug' => 'once-off-dataset'],
            [
                'name' => 'Once-off Dataset',
                'description' => 'Get the raw data without analytics or insights and use it for your own purposes. Ideal for AI training or researchers who just want the data.',
                'price' => 5000.00,
                'image' => '/assets/images/products/dataset.png',
                'stock' => 9999,
                'category_id' => $dataSolutions->id,
                'features' => [
                    '20+ Years of Sanitized, structured CCMA award data',
                    'Available in JSON or CSV formats',
                    'POPIA Compliant Data Entries',
                ],
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'developer-api'],
            [
                'name' => 'Developer API',
                'description' => 'Power your custom applications with direct access to our structured legal dataset API.',
                'price' => 380.00,
                'image' => '/assets/images/products/api.png',
                'stock' => 9999,
                'category_id' => $dataSolutions->id,
                'features' => [
                    'Structured OpenAPI-standard REST endpoints',
                    'Standard API rate limits (1000 req/month)',
                    'Add-ons available to increase rate limits',
                    'Add-on Datasets at Reduced Prices',
                    'Standard Helpdesk Ticket Support',
                    'POPIA Compliant Data Entries',
                ],
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'analytics-dashboard'],
            [
                'name' => 'Pro Analytics',
                'description' => 'No code required. Access to trends and insights through our analytics platform.',
                'price' => 3800.00,
                'image' => '/assets/images/products/analytics.png',
                'stock' => 9999,
                'category_id' => $dataSolutions->id,
                'features' => [
                    'Includes all Developer API features',
                    'No-code Interactive Analytics Dashboard',
                    'Automated Reports & CSV/JSON exports',
                    'Expanded API Endpoint Catalogue',
                    'Increased API rate limits (3000 req/month)',
                    'Priority Helpdesk Ticket Support',
                    'POPIA Compliant Data Entries',
                ],
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'managed-data-pipeline'],
            [
                'name' => 'Managed Data Pipeline',
                'description' => 'Build custom, automated extraction workflows tailored to your specific industry needs. We handle the extraction, transformation, and secure routing of structured data directly into your private ecosystem.',
                'price' => 19500.00,
                'image' => '/assets/images/products/pipeline.png',
                'stock' => 9999,
                'category_id' => $dataSolutions->id,
                'features' => [
                    'Deployed on your own on-premises or cloud infrastructure',
                    'Custom web scraping and ETL data engineering',
                    'Private AI models (LLMs) & secure processing for sensitive data',
                    '24/7 Priority Helpdesk Support',
                    'POPIA Compliant Data Entries',
                ],
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
