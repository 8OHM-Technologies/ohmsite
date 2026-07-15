<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ApiCall;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserOrderController extends Controller
{
    /**
     * Display a listing of the user's orders and subscription stats.
     */
    public function index()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->latest()
            ->get();

        $apiStats = null;
        if ($user->hasApiAccess()) {
            $callsCount = ApiCall::where('user_id', $user->id)
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $limit = $user->getApiCallLimit();
            $remaining = $user->isAdmin() ? 'Unlimited' : max(0, $limit - $callsCount);

            $apiStats = [
                'has_access' => true,
                'used' => $callsCount,
                'limit' => $user->isAdmin() ? 'Unlimited' : $limit,
                'remaining' => $remaining,
            ];
        }

        return Inertia::render('Profile/Orders', [
            'orders' => $orders,
            'apiStats' => $apiStats,
        ]);
    }
}
