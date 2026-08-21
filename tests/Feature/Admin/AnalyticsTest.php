<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Redirect pgsql_coeus to an isolated SQLite in-memory database during tests
        config(['database.connections.pgsql_coeus' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // Create the pipelines_scrapingpipelinemetrics table
        DB::connection('pgsql_coeus')->statement(
            'CREATE TABLE pipelines_scrapingpipelinemetrics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pipeline_name VARCHAR(255) UNIQUE,
                metrics TEXT,
                updated_at DATETIME
            )'
        );
    }

    public function test_guest_cannot_access_admin_analytics()
    {
        $response = $this->get(route('admin.analytics.index'));
        $response->assertRedirect('/login');
    }

    public function test_customer_cannot_access_admin_analytics()
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->get(route('admin.analytics.index'));
        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }

    public function test_admin_can_access_admin_analytics_and_view_scraping_metrics()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Insert mock scraping pipeline metrics
        DB::connection('pgsql_coeus')->table('pipelines_scrapingpipelinemetrics')->insert([
            'pipeline_name' => 'Saflii Labour Court - CCT',
            'metrics' => json_encode([
                'overall' => [
                    'total_scraped' => 393,
                    'active_scraped_count' => 391,
                    'scrape_rate_per_hour' => 571.1,
                    'uptime_seconds' => 2464,
                ],
            ]),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Analytics/Index')
            ->has('scrapingMetrics')
            ->where('scrapingMetrics.0.pipeline_name', 'Saflii Labour Court - CCT')
        );
    }

    public function test_admin_can_refresh_scraping_metrics()
    {
        Http::fake([
            'https://control-plane.8ohm.co.za/api/pipelines/analytics/*' => Http::response(['status' => 'ok'], 200),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.analytics.refresh-scraping'));

        $response->assertRedirect();
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'https://control-plane.8ohm.co.za/api/pipelines/analytics/');
        });
    }
}
