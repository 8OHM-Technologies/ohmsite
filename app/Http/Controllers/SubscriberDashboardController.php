<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;

class SubscriberDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Subscriber/Dashboard', [
            'stats' => [
                ['name' => 'Total Collection Value', 'value' => 'R4,250', 'change' => '12.5%', 'trend' => 'up', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['name' => 'Active Products', 'value' => '3', 'change' => '1', 'trend' => 'up', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['name' => 'Monthly Savings', 'value' => 'R120', 'change' => '5.2%', 'trend' => 'up', 'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                ['name' => 'Pro Status', 'value' => 'Active', 'change' => '0', 'trend' => 'up', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
            ],
            'recentProducts' => Product::with(['category', 'brands'])->latest()->take(2)->get(),
            'weeklySales' => [
                ['day' => 'Mon', 'amount' => 120, 'height' => 40],
                ['day' => 'Tue', 'amount' => 200, 'height' => 60],
                ['day' => 'Wed', 'amount' => 150, 'height' => 50],
                ['day' => 'Thu', 'amount' => 300, 'height' => 80],
                ['day' => 'Fri', 'amount' => 250, 'height' => 70],
                ['day' => 'Sat', 'amount' => 400, 'height' => 100],
                ['day' => 'Sun', 'amount' => 350, 'height' => 90],
            ],
            'growthRate' => '+15.2%'
        ]);
    }

    public function products()
    {
        // For demonstration, fetch some products to show in the collection
        $products = Product::with(['category', 'brands'])->latest()->take(5)->get();
        return Inertia::render('Subscriber/Products/Index', [
            'products' => $products
        ]);
    }

    public function analytics()
    {
        return Inertia::render('Subscriber/Analytics/Index', [
            'stats' => [
                'revenue' => [
                    'total' => 4250,
                    'growth' => 12.5,
                    'chart' => [1200, 1500, 1300, 1800, 2100, 1900, 2400, 2600, 3100, 2900, 3500, 4250]
                ],
                'orders' => [
                    'total' => 12,
                    'growth' => 5.2
                ],
                'conversion_rate' => [
                    'value' => 8.5,
                    'growth' => 1.2
                ],
                'returning_customers' => [
                    'value' => 100,
                    'growth' => 0
                ]
            ],
            'topProducts' => Product::with('brands')->take(3)->get()->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'sales' => rand(1, 5),
                    'revenue' => $product->price,
                    'brand' => $product->brands->first()?->name ?? 'Premium'
                ];
            }),
            'recentActivity' => [
                ['id' => 1, 'type' => 'order', 'user' => 'Pro Access Updated', 'time' => '2 hours ago', 'amount' => null],
                ['id' => 2, 'type' => 'customer', 'user' => 'New Benefit Unlocked', 'time' => '1 day ago', 'amount' => null],
            ],
            'liveStats' => [
                'active_visitors' => 1,
                'sales_today' => 0,
                'revenue_today' => 0
            ],
            'trafficSources' => [
                'Direct' => 40,
                'Social' => 30,
                'Organic' => 20,
                'Referral' => 10
            ],
            'chartLabels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'currentTimeframe' => '30 Days'
        ]);
    }
}
