<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            'filters' => $request->all(['search', 'status']),
        ]);
    }

    public function show(User $customer)
    {
        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer->load(['orders.items.product', 'favorites']),
            'products' => \App\Models\Product::select('id', 'name', 'price', 'slug')->get(),
        ]);
    }

    public function update(Request $request, User $customer)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'role' => 'required|string|in:customer,admin',
            'api_limit_override' => 'nullable|integer|min:0',
        ]);

        $data = $request->only([
            'first_name',
            'last_name',
            'email',
            'phone',
            'company_name',
            'country',
            'role',
        ]);

        $data['api_limit_override'] = $request->input('api_limit_override') !== '' && $request->input('api_limit_override') !== null
            ? (int) $request->input('api_limit_override')
            : null;

        $customer->update($data);

        return back()->with('success', 'Customer details updated successfully.');
    }

    public function createOrder(Request $request, User $customer)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'status' => 'required|string',
            'payment_status' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'frequency' => 'nullable|string|in:monthly,yearly',
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);

        $order = \App\Models\Order::create([
            'user_id' => $customer->id,
            'email' => $customer->email,
            'first_name' => $customer->first_name ?? 'Manual',
            'last_name' => $customer->last_name ?? 'Order',
            'total_amount' => $request->total_amount,
            'status' => $request->status,
            'payment_status' => $request->payment_status,
            'payment_method' => 'manual',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $request->total_amount,
            'options' => [
                'frequency' => $request->frequency ?? 'monthly',
            ],
        ]);

        // If marked as paid, update user subscription fields
        if ($request->payment_status === 'paid') {
            if (in_array($product->slug, ['developer-api', 'pro-analytics', 'managed-data-pipeline'])) {
                $customer->update([
                    'subscription_status' => 'active',
                    'subscribed_at' => now(),
                    'subscription_code' => $customer->subscription_code ?? 'sub_manual_' . time(),
                ]);
            }
        }

        return back()->with('success', 'Manual order created successfully.');
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
        $this->customerService->markAsVip($customer, ! $customer->is_vip);

        return back()->with('success', 'Customer VIP status updated.');
    }
}
