<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::where('role', 'user')->get();
        $products = \App\Models\Product::all();

        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        $paymentStatuses = ['paid', 'pending', 'failed'];

        for ($i = 0; $i < 30; $i++) {
            $user = $users->random();
            $order = \App\Models\Order::create([
                'user_id' => $user->id,
                'total_amount' => 0,
                'status' => $statuses[array_rand($statuses)],
                'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                'shipping_address' => 'Rruga ' . rand(1, 100) . ', Prishtine, Kosove',
                'phone' => '+383 49 ' . rand(100000, 999999),
                'tracking_number' => 'SHK-' . strtoupper(bin2hex(random_bytes(4))),
                'created_at' => now()->subDays(rand(0, 30)),
            ]);

            $total = 0;
            $itemsCount = rand(1, 3);
            for ($j = 0; $j < $itemsCount; $j++) {
                $product = $products->random();
                $quantity = rand(1, 2);
                $price = $product->price;
                
                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                ]);
                $total += ($price * $quantity);
            }

            $order->update(['total_amount' => $total]);
        }
    }
}
