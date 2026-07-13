<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display the license management dashboard.
     * Shows active subscriptions, one-off purchases, and product catalog.
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        // Filters
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        // License-oriented stats
        $stats = [
            'total_products' => Product::count(),
            'active_licenses' => Order::where('status', 'active')->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'total_customers' => User::where('role', '!=', 'admin')->count(),
        ];

        return Inertia::render('Admin/Inventory/Index', [
            'products' => $products,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category']),
        ]);
    }
}
