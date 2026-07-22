<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Dataset;
use Tests\TestCase;

class DemoControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the /demo page is accessible and returns the correct Inertia view with cases.
     */
    public function test_demo_page_loads_and_passes_cases(): void
    {
        Dataset::create([
            'name' => 'CCMA Arbitration Awards',
            'slug' => 'ccma',
            'is_active' => true,
            'demo_data' => [
                [
                    'title' => 'Sample Case',
                    'employer' => 'Sample Employer',
                ]
            ],
        ]);

        $response = $this->get('/demo');

        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Demo/Analytics/Index')
            ->has('cases')
        );

        $cases = $response->original->getData()['page']['props']['cases'];
        $this->assertNotEmpty($cases);
        $this->assertArrayHasKey('title', $cases[0]);
        $this->assertArrayHasKey('employer', $cases[0]);
    }
}
