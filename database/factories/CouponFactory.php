<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('??????')),
            'type' => 'percentage',
            'value' => $this->faker->numberBetween(5, 50),
            'min_order' => $this->faker->numberBetween(10, 100),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ];
    }
}
