<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Get statistics for the customer management dashboard.
     */
    public function getCustomerStats(): array
    {
        return [
            'total_customers' => User::where('role', 'customer')->count(),
            'active_users' => User::where('role', 'customer')
                ->where('last_active_at', '>=', now()->subDays(30))
                ->count(),
            'vip_customers' => User::where('role', 'customer')
                ->where('is_vip', true)
                ->count(),
            'banned_users' => User::where('role', 'customer')
                ->where('is_banned', true)
                ->count(),
        ];
    }

    /**
     * Get a paginated list of customers with filters and computed metrics.
     */
    public function getCustomers(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->where('role', 'customer')
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_amount')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'vip') {
                    // Use a whereHas or subquery for VIP filter to avoid aggregate issues with paginate
                    $query->whereHas('orders', function($q) {
                        $q->where('status', '!=', 'cancelled');
                    }, '>=', 1, function($q) {
                         // This is a more complex way, let's stick to the simplest effective one:
                    });
                    
                    // Simple and safe way for "VIP Only" filter
                    $query->whereIn('id', function($q) {
                        $q->select('user_id')
                          ->from('orders')
                          ->where('status', '!=', 'cancelled')
                          ->groupBy('user_id')
                          ->havingRaw('SUM(total_amount) >= 1000');
                    });
                }
                if ($status === 'banned') $query->where('is_banned', true);
                if ($status === 'active') $query->where('last_active_at', '>=', now()->subDays(30));
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->through(function ($user) {
                // Ensure is_vip is always in sync with spending for the UI
                $user->is_vip = ((float)$user->total_spent >= 1000);
                return $user;
            })
            ->withQueryString();
    }

    /**
     * Search customers by name or email.
     */
    public function searchCustomers(string $search): Collection
    {
        return User::where('role', 'customer')
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->take(10)
            ->get();
    }

    /**
     * Get currently active customers.
     */
    public function getActiveCustomers(): Collection
    {
        return User::where('role', 'customer')
            ->where('last_active_at', '>=', now()->subDays(30))
            ->get();
    }

    /**
     * Calculate if a customer should be VIP based on spending.
     */
    public function calculateVipStatus(User $user): bool
    {
        $totalSpent = $user->orders()->where('status', '!=', 'cancelled')->sum('total_amount');
        return $totalSpent >= 1000;
    }

    /**
     * Manually mark a customer as VIP.
     */
    public function markAsVip(User $user, bool $status = true): void
    {
        $user->update(['is_vip' => $status]);
    }

    /**
     * Ban a customer.
     */
    public function banCustomer(User $user, string $reason = null): void
    {
        $user->update([
            'is_banned' => true,
            'ban_reason' => $reason
        ]);
    }

    /**
     * Unban a customer.
     */
    public function unbanCustomer(User $user): void
    {
        $user->update([
            'is_banned' => false,
            'ban_reason' => null
        ]);
    }
}
