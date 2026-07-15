<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (file_exists(public_path('sitemap.xml'))) {
            unlink(public_path('sitemap.xml'));
        }

        parent::tearDown();
    }

    public function test_sitemap_command_generates_xml_sitemap_file(): void
    {
        // 1. Arrange: Create products in the database
        $products = Product::factory()->count(3)->create();

        // Ensure sitemap file doesn't exist before generation
        if (file_exists(public_path('sitemap.xml'))) {
            unlink(public_path('sitemap.xml'));
        }

        // 2. Act: Call artisan command
        $this->artisan('sitemap:generate')
            ->expectsOutput('Sitemap generated successfully.')
            ->assertExitCode(0);

        // 3. Assert: File exists and has expected paths
        $this->assertFileExists(public_path('sitemap.xml'));
        $content = file_get_contents(public_path('sitemap.xml'));

        $baseUrl = config('app.url');

        $this->assertStringContainsString("<loc>{$baseUrl}/services</loc>", $content);
        $this->assertStringContainsString("<loc>{$baseUrl}/privacy-policy</loc>", $content);
        $this->assertStringContainsString("<loc>{$baseUrl}/terms</loc>", $content);
        $this->assertStringContainsString("<loc>{$baseUrl}/fair-usage</loc>", $content);

        foreach ($products as $product) {
            $this->assertStringContainsString("<loc>{$baseUrl}/services/{$product->id}</loc>", $content);
        }
    }
}
