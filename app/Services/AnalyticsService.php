<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get the full payload for the admin dashboard.
     */
    public function getDashboardPayload(): array
    {
        $statsData = $this->getStatsData();

        return [
            'stats' => $statsData['stats'],
            'recentProducts' => $this->getRecentProducts(),
            'weeklySales' => $this->getWeeklySales(),
            'growthRate' => $statsData['growthRate'],
        ];
    }

    /**
     * Get stats and growth calculations.
     */
    private function getStatsData(): array
    {
        $thisMonth = [Carbon::now()->startOfMonth(), Carbon::now()];
        $lastMonth = [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];

        // Total Sales (All time)
        $totalSales = Order::where('status', '!=', 'cancelled')->sum('total_amount');
        
        // This Month vs Last Month Stats
        $salesThisMonth = Order::where('status', '!=', 'cancelled')->whereBetween('created_at', $thisMonth)->sum('total_amount');
        $salesLastMonth = Order::where('status', '!=', 'cancelled')->whereBetween('created_at', $lastMonth)->sum('total_amount');
        
        $ordersThisMonth = Order::whereBetween('created_at', $thisMonth)->count();
        $ordersLastMonth = Order::whereBetween('created_at', $lastMonth)->count();
        
        $revenueThisMonth = $salesThisMonth;
        $revenueLastMonth = $salesLastMonth;

        // Growth Calculations
        $salesGrowth = $this->calculateGrowth($salesThisMonth, $salesLastMonth);
        $ordersGrowth = $this->calculateGrowth($ordersThisMonth, $ordersLastMonth);
        $revenueGrowth = $this->calculateGrowth($revenueThisMonth, $revenueLastMonth);

        $returningCustomersRate = $this->getReturningCustomersRate();

        $stats = [
            [
                'name' => 'Total Sales',
                'value' => '€' . number_format($totalSales, 0),
                'change' => $salesGrowth['value'],
                'trend' => $salesGrowth['trend'],
                'icon' => 'M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-12c-1.657 0-3 1.343-3 3s1.343 3 3 3 3 1.343 3 3-1.343 3-3 3m0-12V6a2 2 0 114 0v.341C17.67 7.165 19 9.388 19 12v3.159c0 .538.214 1.055.595 1.436L21 17h-5M9 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
            ],
            [
                'name' => 'Orders',
                'value' => number_format(Order::count()),
                'change' => $ordersGrowth['value'],
                'trend' => $ordersGrowth['trend'],
                'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'
            ],
            [
                'name' => 'Revenue',
                'value' => '€' . number_format($totalSales, 0),
                'change' => $revenueGrowth['value'],
                'trend' => $revenueGrowth['trend'],
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
            ],
            [
                'name' => 'Returning Customers',
                'value' => $returningCustomersRate . '%',
                'change' => '+0%',
                'trend' => 'up',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
            ],
        ];

        return [
            'stats' => $stats,
            'growthRate' => $salesGrowth['value'],
        ];
    }

    /**
     * Get weekly sales data for the chart.
     */
    public function getWeeklySales(): array
    {
        $weeklySales = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $amount = Order::whereDate('created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            
            $weeklySales[] = [
                'day' => $date->format('D'),
                'amount' => (float)$amount,
                'height' => 0 
            ];
        }

        $maxAmount = collect($weeklySales)->max('amount') ?: 1;
        foreach ($weeklySales as &$sale) {
            $sale['height'] = round(($sale['amount'] / $maxAmount) * 100);
            if ($sale['height'] < 5 && $sale['amount'] > 0) $sale['height'] = 5; 
        }

        return $weeklySales;
    }

    /**
     * Get recent products with basic details.
     */
    public function getRecentProducts(int $limit = 4)
    {
        return Product::with(['brands', 'category'])
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float)($product->sale_price ?: $product->price),
                'stock' => $product->stock > 0 ? 'In Stock' : 'Out of Stock',
                'image' => $product->image,
            ]);
    }

    /**
     * Calculate growth percentage between current and previous periods.
     */
    private function calculateGrowth($current, $previous): array
    {
        if ($previous == 0) {
            return [
                'value' => $current > 0 ? '+100%' : '+0%',
                'trend' => $current > 0 ? 'up' : 'neutral'
            ];
        }

        $growth = (($current - $previous) / $previous) * 100;
        $formatted = ($growth >= 0 ? '+' : '') . round($growth, 1) . '%';

        return [
            'value' => $formatted,
            'trend' => $growth >= 0 ? 'up' : 'down'
        ];
    }

    /**
     * Calculate the rate of returning customers.
     */
    private function getReturningCustomersRate(): int
    {
        $totalUsersWithOrders = Order::distinct('user_id')->count();
        if ($totalUsersWithOrders === 0) return 0;

        $returningUsersCount = DB::table('orders')
            ->select('user_id')
            ->groupBy('user_id')
            ->having(DB::raw('count(id)'), '>', 1)
            ->get()
            ->count();
            
        return round(($returningUsersCount / $totalUsersWithOrders) * 100);
    }
}
