<?php

namespace App\Services;

use App\Models\User;
use Binkode\Paystack\Support\Customer;
use Binkode\Paystack\Support\Subscription;

class BillingService
{
    /**
     * Ensure a user has a Paystack customer account, then subscribe them to a plan.
     */
    public function subscribeUserToPlan(User $user, string $planCode): array
    {
        // 1. Ensure user has a Paystack customer code
        if (! $user->paystack_customer_code) {
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            $customerRes = Customer::create([
                'email' => $user->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            if (isset($customerRes['data']['customer_code'])) {
                $user->update([
                    'paystack_customer_code' => $customerRes['data']['customer_code'],
                ]);
            }
        }

        // 2. Create the subscription on Paystack
        $subscriptionRes = Subscription::create([
            'customer' => $user->paystack_customer_code,
            'plan' => $planCode,
        ]);

        if (isset($subscriptionRes['status']) && $subscriptionRes['status'] === true) {
            $user->update([
                'subscription_code' => $subscriptionRes['data']['subscription_code'],
                'subscription_status' => 'active',
                'subscribed_at' => now(),
            ]);
        }

        return $subscriptionRes;
    }
}
