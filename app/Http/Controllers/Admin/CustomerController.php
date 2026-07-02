<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Services\CustomerService;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request)
    {
        return Inertia::render('Admin/Customers/Index', [
            'customers' => $this->customerService->getCustomers($request->all()),
            'stats' => $this->customerService->getCustomerStats(),
            'filters' => $request->all(['search', 'status'])
        ]);
    }

    public function show(User $customer)
    {
        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer->load(['orders.items.product', 'favorites'])
        ]);
    }

    public function toggleStatus(Request $request, User $customer)
    {
        if ($customer->is_banned) {
            $this->customerService->unbanCustomer($customer);
            $message = 'Customer unbanned successfully.';
        } else {
            $this->customerService->banCustomer($customer, $request->reason);
            $message = 'Customer banned successfully.';
        }

        return back()->with('success', $message);
    }

    public function toggleVip(User $customer)
    {
        $this->customerService->markAsVip($customer, !$customer->is_vip);
        
        return back()->with('success', 'Customer VIP status updated.');
    }
}
