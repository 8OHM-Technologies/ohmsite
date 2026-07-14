<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_product_management(): void
    {
        $response = $this->get(route('admin.products.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_product_management(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->get(route('admin.products.index'));
        $response->assertRedirect(route('home'));
    }

    public function test_admin_can_view_products_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Test Product']);

        $response = $this->actingAs($admin)->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Product');
    }

    public function test_admin_can_update_product_features_and_preserve_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'features' => ['Feature A', 'Feature B'],
        ]);

        // Put request to reorder and add features
        $response = $this->actingAs($admin)->put(route('admin.products.update', $product->id), [
            'name' => 'Updated Product Name',
            'category_id' => $category->id,
            'price' => 199.99,
            'features' => ['Feature C', 'Feature B', 'Feature A'], // Reordered and modified
        ]);

        $response->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertEquals(['Feature C', 'Feature B', 'Feature A'], $product->features);
    }
}
