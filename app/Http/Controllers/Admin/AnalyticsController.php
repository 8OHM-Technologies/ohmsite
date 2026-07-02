<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $timeframe = $request->get('timeframe', '30 Days');
        
        $startDate = match($timeframe) {
            'Today' => Carbon::today(),
            '7 Days' => Carbon::now()->subDays(7),
            '12 Months' => Carbon::now()->subMonths(12),
            default => Carbon::now()->subDays(30),
        };

        // Monthly Revenue & Orders Chart (Last 12 months)
        $revenueChart = [];
        $ordersChart = [];
        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M');
            $revenueChart[] = (float) Order::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            
            $ordersChart[] = Order::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
        }

        // Current Period vs Previous Period for KPI Cards
        $daysDiff = max(1, $startDate->diffInDays(Carbon::now()));
        $prevStartDate = (clone $startDate)->subDays($daysDiff);
        $prevEndDate = (clone $startDate);

        $revThisPeriod = Order::where('status', '!=', 'cancelled')->where('created_at', '>=', $startDate)->sum('total_amount');
        $revLastPeriod = Order::where('status', '!=', 'cancelled')->whereBetween('created_at', [$prevStartDate, $prevEndDate])->sum('total_amount');
        
        $ordThisPeriod = Order::where('created_at', '>=', $startDate)->count();
        $ordLastPeriod = Order::whereBetween('created_at', [$prevStartDate, $prevEndDate])->count();

        // Returning Customers Calculation
        $totalUsersWithOrders = Order::distinct('user_id')->count();
        $returningUsersCount = DB::table('orders')
            ->select('user_id')
            ->groupBy('user_id')
            ->having(DB::raw('count(id)'), '>', 1)
            ->get()
            ->count();
            
        $returningRate = $totalUsersWithOrders > 0 ? round(($returningUsersCount / $totalUsersWithOrders) * 100, 1) : 0;

        $stats = [
            'revenue' => [
                'total' => (float) $revThisPeriod,
                'growth' => $this->calculateGrowthPercent($revThisPeriod, $revLastPeriod),
                'chart' => $revenueChart
            ],
            'orders' => [
                'total' => $ordThisPeriod,
                'growth' => $this->calculateGrowthPercent($ordThisPeriod, $ordLastPeriod),
                'chart' => $ordersChart
            ],
            'conversion_rate' => [
                'value' => 3.2, 
                'growth' => +0.4,
            ],
            'returning_customers' => [
                'value' => $returningRate,
                'growth' => +1.2,
            ]
        ];

        // Top Products by Sales
        $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(unit_price * quantity) as total_revenue'))
            ->where('created_at', '>=', $startDate)
            ->with(['product' => function($q) {
                $q->with('brands');
            }])
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->take(5)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product->name ?? 'Deleted Product',
                    'brand' => $item->product->brands->pluck('name')->first() ?? 'N/A',
                    'sales' => (int)$item->total_sold,
                    'revenue' => (float)$item->total_revenue,
                    'growth' => '+15%', 
                    'image' => $item->product->image ?? null
                ];
            });

        // Recent Activity
        $recentActivity = Order::with('user')
            ->latest()
            ->take(6)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'type' => 'order',
                    'user' => $order->first_name . ' ' . $order->last_name,
                    'action' => 'placed a new order',
                    'amount' => '€' . number_format($order->total_amount, 2),
                    'time' => $order->created_at->diffForHumans(),
                    'status' => $order->status
                ];
            });

        // Live Stats for Today
        $liveStats = [
            'active_visitors' => rand(10, 50),
            'sales_today' => Order::whereDate('created_at', Carbon::today())->count(),
            'revenue_today' => (float) Order::whereDate('created_at', Carbon::today())
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount')
        ];

        // Traffic Sources Calculation (Mocked logic for visualization)
        $trafficSources = [
            'Direct' => Order::where('payment_method', 'cash')->count(),
            'Social' => Order::where('status', 'pending')->count(),
            'Search' => Order::where('status', 'delivered')->count(),
            'Referral' => Order::where('status', 'processing')->count(),
        ];

        return Inertia::render('Admin/Analytics/Index', [
            'stats' => $stats,
            'topProducts' => $topProducts,
            'recentActivity' => $recentActivity,
            'liveStats' => $liveStats,
            'trafficSources' => $trafficSources,
            'chartLabels' => $labels,
            'currentTimeframe' => $timeframe
        ]);
    }

    private function calculateGrowthPercent($current, $previous): float
    {
        if ($previous == 0) return $current > 0 ? 100.0 : 0.0;
        return (float) round((($current - $previous) / $previous) * 100, 1);
    }
}
