<?php

namespace Tests\Feature\Admin;

use App\Models\Dataset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatasetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dataset_management(): void
    {
        $response = $this->get(route('admin.datasets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_dataset_management(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->get(route('admin.datasets.index'));
        $response->assertRedirect(route('home'));
    }

    public function test_admin_can_view_datasets_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dataset = Dataset::create([
            'name' => 'Test Dataset',
            'slug' => 'test-dataset',
            'description' => 'Description test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.datasets.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Dataset');
    }

    public function test_admin_can_create_dataset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.datasets.store'), [
            'name' => 'New Dataset',
            'slug' => 'new-dataset',
            'description' => 'A new dataset description',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.datasets.index'));
        $this->assertDatabaseHas('datasets', [
            'name' => 'New Dataset',
            'slug' => 'new-dataset',
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_create_dataset_with_duplicate_slug(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Dataset::create([
            'name' => 'First Dataset',
            'slug' => 'common-slug',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.datasets.store'), [
            'name' => 'Second Dataset',
            'slug' => 'common-slug',
            'description' => 'Another one',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_dataset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dataset = Dataset::create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.datasets.update', $dataset->id), [
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => 'New description',
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('admin.datasets.index'));
        $this->assertDatabaseHas('datasets', [
            'id' => $dataset->id,
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_dataset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dataset = Dataset::create([
            'name' => 'To Delete',
            'slug' => 'to-delete',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.datasets.destroy', $dataset->id));

        $response->assertRedirect(route('admin.datasets.index'));
        $this->assertDatabaseMissing('datasets', [
            'id' => $dataset->id,
        ]);
    }
}
